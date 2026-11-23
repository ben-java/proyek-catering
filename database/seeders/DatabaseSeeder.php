<?php

namespace Database\Seeders;

use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        // Jangan pernah menanam password statis: pakai ADMIN_PASSWORD bila diisi,
        // kalau tidak buat acak dan tampilkan sekali saat seeding.
        $adminPassword = env('ADMIN_PASSWORD') ?: \Illuminate\Support\Str::password(20);

        if (strlen((string) $adminPassword) < 12) {
            throw new \RuntimeException('ADMIN_PASSWORD minimal 12 karakter.');
        }
        User::query()->updateOrCreate(
            ['email' => 'admin'],
            ['name' => 'Admin Catering', 'password' => $adminPassword]
        );
        $this->command?->warn("Kredensial admin: admin / {$adminPassword}");

        // Menu contoh
        $menu = [
            ['nama_menu' => 'Nasi Ayam Goreng', 'jenis' => 'harian', 'harga_satuan' => 15000],
            ['nama_menu' => 'Nasi Rendang', 'jenis' => 'harian', 'harga_satuan' => 18000],
            ['nama_menu' => 'Nasi Kotak Ayam', 'jenis' => 'nasi_kotak', 'harga_satuan' => 20000],
            ['nama_menu' => 'Nasi Kotak Rendang', 'jenis' => 'nasi_kotak', 'harga_satuan' => 25000],
            ['nama_menu' => 'Nasi Kotak Sate', 'jenis' => 'nasi_kotak', 'harga_satuan' => 25000],
            ['nama_menu' => 'Tumpeng Mini', 'jenis' => 'tumpeng', 'harga_satuan' => 350000],
            ['nama_menu' => 'Tumpeng Besar', 'jenis' => 'tumpeng', 'harga_satuan' => 500000],
            ['nama_menu' => 'Ayam Goreng', 'jenis' => 'harian', 'harga_satuan' => 8000],
        ];
        foreach ($menu as $m) {
            Menu::create($m);
        }

        // 30 pesanan contoh + rincian
        Pesanan::factory()->count(30)->create()->each(function (Pesanan $pesanan) {
            $items = Menu::inRandomOrder()->take(rand(1, 3))->get();
            $total = 0;
            foreach ($items as $menuItem) {
                $jumlah = rand(1, 10);
                $subtotal = (float) $menuItem->harga_satuan * $jumlah;
                $total += $subtotal;
                DetailPesanan::create([
                    'pesanan_id' => $pesanan->id,
                    'menu_id' => $menuItem->id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $menuItem->harga_satuan,
                    'subtotal' => $subtotal,
                ]);
            }
            $pesanan->update(['total_harga' => $total]);
        });
    }
}
