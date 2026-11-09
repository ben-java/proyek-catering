<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'nama_menu' => fake()->unique()->randomElement([
                'Nasi Ayam Goreng',
                'Nasi Rendang',
                'Nasi Kotak Ayam',
                'Nasi Kotak Rendang',
                'Nasi Kotak Sate',
                'Tumpeng Mini',
                'Tumpeng Besar',
                'Ayam Goreng',
            ]),
            'jenis' => fake()->randomElement(['harian', 'nasi_kotak', 'tumpeng']),
            'harga_satuan' => fake()->numberBetween(5000, 500000),
            'tersedia' => true,
        ];
    }
}
