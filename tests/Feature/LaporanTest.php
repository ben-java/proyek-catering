<?php

namespace Tests\Feature;

use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrder($tanggal, $bayar = 'lunas')
    {
        $menu = Menu::factory()->create(['harga_satuan' => 10000]);
        $pesanan = Pesanan::factory()->create([
            'tanggal_kirim' => $tanggal,
            'status_bayar' => $bayar,
        ]);
        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'jumlah' => 3,
            'harga_satuan' => 10000,
            'subtotal' => 30000,
        ]);
        $pesanan->update(['total_harga' => 30000]);

        return $pesanan;
    }

    public function test_rekap_porsi_shows_total_portion(): void
    {
        $tanggal = now()->addDay()->toDateString();
        $this->seedOrder($tanggal);

        $this->actingAs(User::factory()->create())
            ->get("/laporan/porsi?tanggal={$tanggal}")
            ->assertOk()
            ->assertSee('3');
    }

    public function test_rekap_pendapatan_sums_lunas(): void
    {
        $tanggal = now()->addDay()->toDateString();
        $this->seedOrder($tanggal, 'lunas');
        $this->seedOrder($tanggal, 'belum_lunas');

        $this->actingAs(User::factory()->create())
            ->get("/laporan/pendapatan?tanggal={$tanggal}")
            ->assertOk()
            ->assertSee('30.000');
    }

    public function test_laporan_requires_auth(): void
    {
        $this->get('/laporan/porsi')->assertRedirect('/login');
    }
}
