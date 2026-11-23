<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression keamanan hasil pentest (redteam -> blueteam).
 *
 * Setiap test memetakan satu temuan: header keamanan, brute-force login,
 * banjir formulir publik, HTTP 500 karena parameter array, overflow jumlah,
 * dan penomoran pesanan yang rusak di urutan >= 1000.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['password' => bcrypt('password')]);
    }

    private function menu(): Menu
    {
        return Menu::query()->first() ?? Menu::create([
            'nama_menu' => 'Nasi Ayam Goreng',
            'jenis' => 'harian',
            'harga_satuan' => 15000,
            'tersedia' => true,
        ]);
    }

    private function payload(array $override = []): array
    {
        $menu = $this->menu();

        return array_merge([
            'nama_pemesan' => 'Budi',
            'no_hp' => '08123456789',
            'alamat_kirim' => 'Jl. Contoh 1',
            'tanggal_kirim' => now()->addDays(2)->toDateString(),
            'items' => [['menu_id' => $menu->id, 'jumlah' => 2]],
        ], $override);
    }

    public function test_response_membawa_header_keamanan(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        $this->admin();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', ['email' => 'admin', 'password' => "salah{$i}"])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'admin', 'password' => 'salah6'])
            ->assertStatus(429);
    }

    public function test_formulir_publik_dibatasi_laju(): void
    {
        $payload = $this->payload();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/pesan', $payload)->assertStatus(302);
        }

        $this->post('/pesan', $payload)->assertStatus(429);
    }

    public function test_parameter_array_tidak_lagi_menghasilkan_500(): void
    {
        // /login: old('email') berisi array setelah POST gagal validasi.
        $this->post('/login', ['email' => ['x'], 'password' => ['y']])->assertStatus(302);
        $this->get('/login')->assertOk();

        // /pesan: old('nama_pemesan') berisi array.
        $this->post('/pesan', array_merge($this->payload(), ['nama_pemesan' => ['x']]))->assertStatus(302);
        $this->get('/pesan')->assertOk();

        // Filter admin dengan query array.
        $this->actingAs($this->admin());
        $this->get('/pesanan?tanggal[]=x&status[]=y')->assertOk();
        $this->get('/menu?q[]=x&jenis[]=y')->assertOk();
    }

    public function test_jumlah_tidak_bisa_overflow(): void
    {
        $menu = $this->menu();

        $this->post('/pesan', $this->payload([
            'items' => [['menu_id' => $menu->id, 'jumlah' => 9223372036854775807]],
        ]))->assertSessionHasErrors('items.0.jumlah');

        $this->post('/pesan', $this->payload([
            'items' => [['menu_id' => $menu->id, 'jumlah' => 201]],
        ]))->assertSessionHasErrors('items.0.jumlah');

        $this->assertSame(0, Pesanan::query()->count());
    }

    public function test_jumlah_baris_item_dan_alamat_dibatasi(): void
    {
        $menu = $this->menu();
        $items = [];
        for ($i = 0; $i < 21; $i++) {
            $items[] = ['menu_id' => $menu->id, 'jumlah' => 1];
        }

        $this->post('/pesan', $this->payload(['items' => $items]))
            ->assertSessionHasErrors('items');

        $this->post('/pesan', $this->payload(['alamat_kirim' => str_repeat('A', 501)]))
            ->assertSessionHasErrors('alamat_kirim');

        $this->assertSame(0, Pesanan::query()->count());
    }

    public function test_penomoran_pesanan_lewat_999_tidak_bentrok(): void
    {
        DB::table('pesanan')->insert([
            'no_pesanan' => 'PO-'.now()->format('Ymd').'-1000',
            'nama_pemesan' => 'Lama',
            'no_hp' => '0812',
            'alamat_kirim' => 'Jl',
            'tanggal_kirim' => now()->addDays(2)->toDateString(),
            'total_harga' => 0,
            'status' => 'menunggu_konfirmasi',
            'status_bayar' => 'belum_lunas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/pesan', $this->payload())->assertStatus(302);

        $baru = Pesanan::query()->where('nama_pemesan', 'Budi')->firstOrFail();
        $this->assertSame('PO-'.now()->format('Ymd').'-1001', $baru->no_pesanan);
    }

    public function test_header_keamanan_ikut_pada_halaman_error(): void
    {
        $notFound = $this->get('/tidak-ada');
        $notFound->assertNotFound();
        $notFound->assertHeader('X-Frame-Options', 'DENY');

        // CSRF dimatikan di environment testing; 419 diverifikasi lewat uji HTTP
        // langsung. Di sini cukup pastikan redirect validasi juga berheader.
        $redirect = $this->post('/pesan', ['nama_pemesan' => 'x']);
        $redirect->assertStatus(302);
        $redirect->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_ukuran_halaman_dibatasi(): void
    {
        $this->actingAs($this->admin());
        $tanggal = now()->addDays(2)->toDateString();
        Pesanan::factory()->count(30)->create(['tanggal_kirim' => $tanggal]);

        $response = $this->get('/pesanan?tanggal='.$tanggal);
        $response->assertOk();
        $paginator = $response->viewData('pesanan');

        $this->assertLessThanOrEqual(200, $paginator->perPage());
        $this->assertSame(30, $paginator->total());
    }

    public function test_tanggal_kirim_tidak_bisa_tahun_9999(): void
    {
        $this->post('/pesan', $this->payload(['tanggal_kirim' => '9999-12-31']))
            ->assertSessionHasErrors('tanggal_kirim');

        $this->assertSame(0, Pesanan::count());
    }

    public function test_status_tidak_bisa_melompat(): void
    {
        $pesanan = Pesanan::factory()->create(['status' => 'menunggu_konfirmasi']);
        $this->actingAs($this->admin());

        $this->post("/pesanan/{$pesanan->id}/status", ['status' => 'selesai'])->assertRedirect();
        $this->assertSame('menunggu_konfirmasi', $pesanan->fresh()->status->value);

        $this->post("/pesanan/{$pesanan->id}/status", ['status' => 'diproses'])->assertRedirect();
        $this->assertSame('diproses', $pesanan->fresh()->status->value);
    }

    public function test_harga_menu_tidak_bisa_inf_atau_melebihi_kolom(): void
    {
        $this->actingAs($this->admin());

        foreach (['1e309', '99999999999999999999', '100000000'] as $harga) {
            $this->post('/menu', [
                'nama_menu' => 'Uji '.$harga,
                'jenis' => 'harian',
                'harga_satuan' => $harga,
            ])->assertSessionHasErrors('harga_satuan');
        }

        $this->post('/menu', ['nama_menu' => 'Wajar', 'jenis' => 'harian', 'harga_satuan' => 12000])
            ->assertRedirect('/menu');
    }

    public function test_login_menolak_identifier_terlalu_panjang(): void
    {
        $this->post('/login', ['email' => str_repeat('a', 300), 'password' => 'x'])
            ->assertSessionHasErrors('email');
    }
}
