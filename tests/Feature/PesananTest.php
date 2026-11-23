<?php

namespace Tests\Feature;

use App\Enums\StatusBayar;
use App\Enums\StatusPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function pesananPayload(array $overrides = []): array
    {
        $menuA = Menu::factory()->create(['harga_satuan' => 20000]);
        $menuB = Menu::factory()->create(['harga_satuan' => 8000]);

        return array_merge([
            'nama_pemesan' => 'Budi Santoso',
            'no_hp' => '081234567890',
            'alamat_kirim' => 'Jl. Merdeka No.10',
            'tanggal_kirim' => now()->addDay()->toDateString(),
            'items' => [
                ['menu_id' => $menuA->id, 'jumlah' => 2],
                ['menu_id' => $menuB->id, 'jumlah' => 1],
            ],
        ], $overrides);
    }

    public function test_customer_storefront_is_visible_to_guests(): void
    {
        $this->get('/pesan')->assertOk()->assertSee('Meja penuh rasa.');
    }

    public function test_customer_storefront_is_visible_to_signed_in_admin(): void
    {
        $this->actingAs($this->admin())
            ->get('/pesan')->assertOk()->assertSee('Meja penuh rasa.');

        $this->get('/pesanan/create')->assertOk()->assertDontSee('Meja penuh rasa.');
    }

    public function test_guest_cannot_access_order_list(): void
    {
        $this->get('/pesanan')->assertRedirect('/login');
    }

    public function test_admin_can_create_order_with_auto_total(): void
    {
        $this->actingAs($this->admin())
            ->post('/pesanan', $this->pesananPayload())
            ->assertRedirect('/pesanan');

        $pesanan = Pesanan::first();

        $this->assertSame('Budi Santoso', $pesanan->nama_pemesan);
        $this->assertSame(48000.0, (float) $pesanan->total_harga); // 2*20000 + 1*8000
        $this->assertTrue($pesanan->status === StatusPesanan::MenungguKonfirmasi);
        $this->assertTrue($pesanan->status_bayar === StatusBayar::BelumLunas);
        $this->assertStringStartsWith('PO-', $pesanan->no_pesanan);
        $this->assertCount(2, $pesanan->detailPesanan);
    }

    public function test_create_order_requires_items(): void
    {
        $payload = $this->pesananPayload(['items' => []]);

        $this->actingAs($this->admin())
            ->post('/pesanan', $payload)
            ->assertSessionHasErrors('items');
    }

    public function test_create_order_requires_tomorrow_or_later(): void
    {
        $payload = $this->pesananPayload(['tanggal_kirim' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin())
            ->post('/pesanan', $payload)
            ->assertSessionHasErrors('tanggal_kirim');

        $this->assertSame(0, Pesanan::count());
    }

    public function test_admin_can_change_status_and_payment(): void
    {
        $pesanan = Pesanan::factory()->create([
            'status' => 'menunggu_konfirmasi',
            'status_bayar' => 'belum_lunas',
        ]);
        $this->actingAs($this->admin());

        $this->post("/pesanan/{$pesanan->id}/status", ['status' => 'diproses'])
            ->assertRedirect();

        $this->assertSame('diproses', $pesanan->fresh()->status->value);

        $this->post("/pesanan/{$pesanan->id}/bayar")->assertRedirect();
        $this->assertSame('lunas', $pesanan->fresh()->status_bayar->value);
    }

    public function test_public_guest_can_create_order_via_pesan(): void
    {
        $this->post('/pesan', $this->pesananPayload())
            ->assertRedirect('/pesan/terima-kasih');

        $this->assertSame(1, Pesanan::count());
    }
}
