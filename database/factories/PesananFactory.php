<?php

namespace Database\Factories;

use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pesanan>
 */
class PesananFactory extends Factory
{
    protected $model = Pesanan::class;

    public function definition(): array
    {
        return [
            'nama_pemesan' => fake()->name(),
            'no_hp' => '08'.fake()->numerify('##########'),
            'alamat_kirim' => fake()->address(),
            'tanggal_kirim' => fake()->dateTimeBetween('+1 day', '+7 days')->format('Y-m-d'),
            'total_harga' => 0,
            'status' => fake()->randomElement([
                'menunggu_konfirmasi', 'diproses', 'diantar', 'selesai',
            ]),
            'status_bayar' => fake()->randomElement(['belum_lunas', 'lunas']),
            'catatan' => fake()->optional(0.4)->sentence(),
        ];
    }
}
