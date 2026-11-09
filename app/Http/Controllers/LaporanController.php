<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    public function porsi(Request $request)
    {
        $tanggal = $this->validatedDate($request);

        $rekap = DetailPesanan::query()
            ->join('pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
            ->join('menu', 'menu.id', '=', 'detail_pesanan.menu_id')
            ->whereDate('pesanan.tanggal_kirim', $tanggal)
            ->groupBy('menu.id', 'menu.nama_menu', 'menu.jenis')
            ->selectRaw('menu.nama_menu, menu.jenis, SUM(detail_pesanan.jumlah) as total_porsi')
            ->orderByDesc('total_porsi')
            ->get();

        $totalPorsi = $rekap->sum('total_porsi');

        return view('laporan.porsi', compact('tanggal', 'rekap', 'totalPorsi'));
    }

    public function pendapatan(Request $request)
    {
        $tanggal = $this->validatedDate($request);

        $daftar = Pesanan::tanggal($tanggal)->with('detailPesanan.menu')->orderByDesc('created_at')->get();

        $statistik = [
            'total_pendapatan' => $daftar->where('status_bayar', 'lunas')->sum('total_harga'),
            'belum_lunas' => $daftar->where('status_bayar', 'belum_lunas')->count(),
            'total_pesanan' => $daftar->count(),
        ];

        return view('laporan.pendapatan', compact('tanggal', 'daftar', 'statistik'));
    }

    /**
     * BUG-15: tanggal malformed tidak boleh memicu HTTP 500.
     */
    private function validatedDate(Request $request): string
    {
        $value = $request->tanggal;

        if ($value) {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable $e) {
                session()->flash('err', 'Tanggal tidak valid, ditampilkan hari ini.');
            }
        }

        return now()->toDateString();
    }
}
