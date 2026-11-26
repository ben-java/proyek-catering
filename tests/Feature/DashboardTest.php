<?php

namespace Tests\Feature;

use App\Enums\StatusPesanan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithMenu(string $tanggal, string $status, string $namaPemesan, string $namaMenu = 'Nasi Rendang', int $jumlah = 2): Pesanan
    {
        $menu = Menu::factory()->create(['nama_menu' => $namaMenu, 'harga_satuan' => 18000]);
        $pesanan = Pesanan::factory()->create([
            'nama_pemesan' => $namaPemesan,
            'tanggal_kirim' => $tanggal,
            'status' => $status,
            'total_harga' => 18000 * $jumlah,
        ]);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'jumlah' => $jumlah,
            'harga_satuan' => 18000,
            'subtotal' => 18000 * $jumlah,
        ]);

        return $pesanan;
    }

    public function test_dashboard_shows_active_orders_for_today_and_tomorrow_only(): void
    {
        $hariIni = now()->toDateString();
        $besok = now()->addDay()->toDateString();
        $lusa = now()->addDays(2)->toDateString();
        $kemarin = now()->subDay()->toDateString();

        $orderHariIni = $this->createOrderWithMenu($hariIni, StatusPesanan::MenungguKonfirmasi->value, 'Pemesan Hari Ini', 'Nasi Ayam Goreng', 3);
        $orderBesok = $this->createOrderWithMenu($besok, StatusPesanan::Diproses->value, 'Pemesan Besok', 'Tumpeng Mini', 1);
        $orderSelesai = $this->createOrderWithMenu($hariIni, StatusPesanan::Selesai->value, 'Pemesan Sudah Selesai');
        $orderLusa = $this->createOrderWithMenu($lusa, StatusPesanan::MenungguKonfirmasi->value, 'Pemesan Lusa');
        $orderKemarin = $this->createOrderWithMenu($kemarin, StatusPesanan::Diproses->value, 'Pemesan Kemarin');
        $orderTerhapus = $this->createOrderWithMenu($hariIni, StatusPesanan::Diproses->value, 'Pemesan Terhapus');
        $orderTerhapus->delete();

        $response = $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk();

        $response->assertSee('Pesanan yang Harus Dikerjakan')
            ->assertSee($orderHariIni->no_pesanan)
            ->assertSee('Pemesan Hari Ini')
            ->assertSee('3× Nasi Ayam Goreng')
            ->assertSee($orderBesok->no_pesanan)
            ->assertSee('Pemesan Besok')
            ->assertSee('1× Tumpeng Mini')
            ->assertSee("/pesanan?tanggal={$hariIni}")
            ->assertSee("/pesanan?tanggal={$besok}")
            ->assertDontSee('/laporan/porsi?tanggal=')
            ->assertDontSee($orderSelesai->no_pesanan)
            ->assertDontSee('Pemesan Sudah Selesai')
            ->assertDontSee($orderLusa->no_pesanan)
            ->assertDontSee('Pemesan Lusa')
            ->assertDontSee($orderKemarin->no_pesanan)
            ->assertDontSee('Pemesan Kemarin')
            ->assertDontSee($orderTerhapus->no_pesanan)
            ->assertDontSee('Pemesan Terhapus');
    }

    public function test_dashboard_shortcut_can_advance_order_status(): void
    {
        $pesanan = $this->createOrderWithMenu(now()->toDateString(), StatusPesanan::MenungguKonfirmasi->value, 'Budi');

        $this->actingAs(User::factory()->create())
            ->from('/dashboard')
            ->post("/pesanan/{$pesanan->id}/status", ['status' => StatusPesanan::Diproses->value])
            ->assertRedirect('/dashboard');

        $this->assertSame(StatusPesanan::Diproses, $pesanan->fresh()->status);
    }
}
