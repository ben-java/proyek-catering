@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
    <div class="pagehead">
        <h1>Dashboard</h1>
        <div class="sub">Rekap pesanan hari ini ({{ \Illuminate\Support\Carbon::parse($hariIni)->translatedFormat('d M Y') }})</div>
    </div>

    <div class="stats">
        <div class="stat"><div class="lbl">Pesanan hari ini</div><div class="num">{{ $statistik['pesanan_hari_ini'] }}</div></div>
        <div class="stat"><div class="lbl">Total pendapatan (lunas)</div><div class="num">Rp{{ number_format($statistik['pendapatan_lunas'], 0, ',', '.') }}</div></div>
        <div class="stat"><div class="lbl">Belum lunas</div><div class="num warn">{{ $statistik['belum_lunas'] }}</div></div>
    </div>

    <div class="grid2">
        <div class="card">
            <h2>Pesanan per Status</h2>
            <div class="table-card">
            <table>
                <thead><tr><th>Status</th><th class="text-right">Jumlah</th></tr></thead>
                <tbody>
                    @forelse($statistik['per_status'] as $status => $total)
                        <tr><td>{{ \App\Enums\StatusPesanan::tryFrom($status)?->label() ?? $status }}</td><td class="num">{{ $total }}</td></tr>
                    @empty
                        <tr><td colspan="2">Belum ada pesanan hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="mt">
                <a href="{{ url('/pesanan?tanggal=' . $hariIni) }}" class="btn btn-ghost btn-sm">Lihat pesanan hari ini</a>
            </div>
        </div>

        <div class="card">
            <h2>Menu Terlaris Hari Ini</h2>
            <div class="table-card">
            <table>
                <thead><tr><th>Menu</th><th class="text-right">Porsi</th></tr></thead>
                <tbody>
                    @forelse($statistik['menu_terlaris'] as $m)
                        <tr><td>{{ $m->nama_menu }}</td><td class="num">{{ $m->total }}</td></tr>
                    @empty
                        <tr><td colspan="2">Belum ada data hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection
