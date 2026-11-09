<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Pesanan;

class DashboardController extends Controller
{
    public function index()
    {
        $hariIni = now()->toDateString();

        $statistik = [
            'pesanan_hari_ini' => Pesanan::tanggal($hariIni)->count(),
            'pendapatan_lunas' => Pesanan::tanggal($hariIni)->where('status_bayar', 'lunas')->sum('total_harga'),
            'belum_lunas' => Pesanan::tanggal($hariIni)->where('status_bayar', 'belum_lunas')->count(),
            'per_status' => Pesanan::tanggal($hariIni)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'menu_terlaris' => DetailPesanan::query()
                ->join('pesanan', 'pesanan.id', '=', 'detail_pesanan.pesanan_id')
                ->join('menu', 'menu.id', '=', 'detail_pesanan.menu_id')
                ->whereDate('pesanan.tanggal_kirim', $hariIni)
                ->groupBy('menu.id', 'menu.nama_menu')
                ->selectRaw('menu.nama_menu, SUM(detail_pesanan.jumlah) as total')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ];

        return view('dashboard.index', compact('statistik', 'hariIni'));
    }
}
