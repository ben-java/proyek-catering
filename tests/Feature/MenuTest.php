<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_menu(): void
    {
        $this->get('/menu')->assertRedirect('/login');
    }

    public function test_admin_can_create_menu(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/menu', [
                'nama_menu' => 'Nasi Uduk',
                'jenis' => 'harian',
                'harga_satuan' => 12000,
                'tersedia' => 1,
            ])->assertRedirect('/menu');

        $this->assertDatabaseHas('menu', ['nama_menu' => 'Nasi Uduk', 'harga_satuan' => 12000]);
    }

    public function test_create_menu_requires_numeric_price(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/menu', ['nama_menu' => 'X', 'jenis' => 'harian', 'harga_satuan' => 'abc'])
            ->assertSessionHasErrors('harga_satuan');
    }

    public function test_admin_can_toggle_availability(): void
    {
        $menu = Menu::factory()->create(['tersedia' => true]);

        $this->actingAs(User::factory()->create())
            ->patch("/menu/{$menu->id}/tersedia")
            ->assertRedirect();

        $this->assertFalse($menu->fresh()->tersedia);
    }

    public function test_menu_used_in_order_cannot_be_deleted(): void
    {
        $menu = Menu::factory()->create();
        $pesanan = Pesanan::factory()->create();
        \App\Models\DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'jumlah' => 1,
            'harga_satuan' => $menu->harga_satuan,
            'subtotal' => $menu->harga_satuan,
        ]);

        $this->actingAs(User::factory()->create())
            ->delete("/menu/{$menu->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('menu', ['id' => $menu->id]);
    }
}
