<?php

namespace Database\Factories;

use App\Models\DetailPesanan;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailPesanan>
 */
class DetailPesananFactory extends Factory
{
    protected $model = DetailPesanan::class;

    public function definition(): array
    {
        $menu = Menu::inRandomOrder()->first() ?? Menu::factory()->create();
        $jumlah = fake()->numberBetween(1, 10);

        return [
            'menu_id' => $menu->id,
            'jumlah' => $jumlah,
            'harga_satuan' => $menu->harga_satuan,
            'subtotal' => $menu->harga_satuan * $jumlah,
        ];
    }
}
