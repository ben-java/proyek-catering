<?php

namespace Tests\Feature;

use App\Enums\StatusPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixesRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function basePayload(array $overrides = []): array
    {
        $menu = Menu::factory()->create(['tersedia' => true, 'harga_satuan' => 10000]);

        return array_merge([
            'nama_pemesan' => 'Regresi',
            'no_hp' => '081234567890',
            'alamat_kirim' => 'Jl. Regresi',
            'tanggal_kirim' => now()->addDay()->toDateString(),
            'items' => [['menu_id' => $menu->id, 'jumlah' => 1]],
        ], $overrides);
    }

    public function test_inactive_menu_is_rejected(): void
    {
        $menu = Menu::factory()->create(['tersedia' => false]);

        $this->actingAs($this->admin())
            ->post('/pesanan', $this->basePayload(['items' => [['menu_id' => $menu->id, 'jumlah' => 1]]]))
            ->assertSessionHasErrors('items.0.menu_id');

        $this->assertSame(0, Pesanan::count());
    }

    public function test_phone_rejects_letters(): void
    {
        $this->actingAs($this->admin())
            ->post('/pesanan', $this->basePayload(['no_hp' => 'bukan-nomor']))
            ->assertSessionHasErrors('no_hp');

        $this->assertSame(0, Pesanan::count());
    }

    public function test_today_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/pesanan', $this->basePayload(['tanggal_kirim' => now()->toDateString()]))
            ->assertSessionHasErrors('tanggal_kirim');

        $this->assertSame(0, Pesanan::count());
    }

    public function test_order_search_does_not_bypass_filters(): void
    {
        $hariA = now()->addDay()->toDateString();
        $hariB = now()->addDays(2)->toDateString();
        $a = Pesanan::factory()->create(['tanggal_kirim' => $hariA, 'nama_pemesan' => 'Unik A']);
        $b = Pesanan::factory()->create(['tanggal_kirim' => $hariB, 'nama_pemesan' => 'Unik B']);

        $this->actingAs($this->admin())
            ->get('/pesanan?tanggal='.$hariA.'&q='.$a->no_pesanan)
            ->assertOk()
            ->assertSee($a->no_pesanan)
            ->assertDontSee($b->no_pesanan);
    }

    public function test_order_creation_survives_deleting_older_order(): void
    {
        $hari = now()->addDay()->toDateString();
        $a = Pesanan::factory()->create(['tanggal_kirim' => $hari]);
        Pesanan::factory()->create(['tanggal_kirim' => $hari]);

        $this->actingAs($this->admin());
        $this->delete("/pesanan/{$a->id}")->assertRedirect();

        $new = $this->post('/pesanan', $this->basePayload(['tanggal_kirim' => $hari]))->assertRedirect();

        $this->assertTrue($new->isRedirect());

        $nos = Pesanan::query()->pluck('no_pesanan')->unique();
        $this->assertSame($nos->count(), Pesanan::count(), 'nomor pesanan harus unik setelah penghapusan');
    }

    public function test_detail_renders_for_all_status(): void
    {
        $this->actingAs($this->admin());

        foreach (StatusPesanan::cases() as $status) {
            $p = Pesanan::factory()->create(['status' => $status->value]);
            $this->get("/pesanan/{$p->id}")->assertOk();
        }
    }

    public function test_report_invalid_date_does_not_crash(): void
    {
        $this->actingAs($this->admin())
            ->get('/laporan/porsi?tanggal=bukan-tanggal')
            ->assertOk();

        $this->actingAs($this->admin())
            ->get('/laporan/pendapatan?tanggal=bukan-tanggal')
            ->assertOk();
    }

    public function test_all_orders_of_a_day_render_in_one_page(): void
    {
        $hari = now()->addDay()->toDateString();
        $orders = Pesanan::factory()->count(25)->create(['tanggal_kirim' => $hari]);
        $numbers = $orders->pluck('no_pesanan');

        $response = $this->actingAs($this->admin())
            ->get('/pesanan?tanggal='.$hari)
            ->assertOk();

        foreach ($numbers as $no) {
            $response->assertSee($no);
        }
    }

    public function test_pagination_renders_numeric_links(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Menu::query()->create([
                'nama_menu' => "Menu $i",
                'jenis' => 'harian',
                'harga_satuan' => 10000,
                'tersedia' => true,
            ]);
        }

        $html = $this->actingAs($this->admin())
            ->get('/menu')
            ->assertOk()
            ->getContent();

        // Tidak boleh ada URL ter-encode/nested (mis. ?page=http%3A...%3D1)
        $this->assertStringNotContainsString('page=http', $html);

        preg_match_all('/href="([^"]*)page=\d+"/', $html, $m);
        $this->assertNotEmpty($m[1], 'pagination harus memuat link halaman numerik');

        foreach ($m[1] as $href) {
            $this->assertStringNotContainsString('%3A', $href, 'href harus angka, bukan URL ter-encode');
            $this->assertStringNotContainsString('page=http', $href, 'tidak boleh ada URL page bersarang');
        }

        $this->assertEquals(1, substr_count($html, 'aria-current="page"'), 'tepat satu halaman aktif');
    }
}
