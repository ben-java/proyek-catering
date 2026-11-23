<?php

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PorsiTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_report_only_has_menu_type_and_portions(): void
    {
        $rekap = collect([(object) ['nama_menu' => 'Menu A', 'jenis' => 'harian', 'total_porsi' => 3]]);

        $this->view('laporan.porsi', ['tanggal' => '2031-05-21', 'rekap' => $rekap, 'totalPorsi' => 3])
            ->assertSee('Menu A')
            ->assertSee('Total Porsi')
            ->assertDontSee('Proporsi')
            ->assertDontSee('Persentase')
            ->assertDontSee('porsi-pie')
            ->assertDontSee('%');
    }

    public function test_empty_report_uses_three_columns(): void
    {
        $this->view('laporan.porsi', ['tanggal' => '2031-05-21', 'rekap' => collect(), 'totalPorsi' => 0])
            ->assertSee('Tidak ada pesanan pada tanggal ini.')
            ->assertSee('colspan="3"', false)
            ->assertDontSee('diagram');
    }
}
