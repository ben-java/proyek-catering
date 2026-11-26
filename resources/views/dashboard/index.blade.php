@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
    <div class="pagehead pagehead-dashboard">
        <div>
            <div class="eyebrow">Ringkasan operasional</div>
            <h1>Dashboard</h1>
            <div class="sub">Rekap pesanan hari ini · {{ \Illuminate\Support\Carbon::parse($hariIni)->translatedFormat('d M Y') }}</div>
        </div>
        <a href="{{ url('/pesanan/create') }}" class="btn btn-primary">+ Pesanan baru</a>
    </div>

    <div class="stats">
        <div class="stat stat-main"><div class="stat-icon">↗</div><div><div class="lbl">Pesanan hari ini</div><div class="num">{{ $statistik['pesanan_hari_ini'] }}</div></div></div>
        <div class="stat"><div class="stat-icon">Rp</div><div><div class="lbl">Pendapatan lunas</div><div class="num">Rp{{ number_format($statistik['pendapatan_lunas'], 0, ',', '.') }}</div></div></div>
        <div class="stat stat-alert"><div class="stat-icon">!</div><div><div class="lbl">Belum lunas</div><div class="num warn">{{ $statistik['belum_lunas'] }}</div></div></div>
    </div>

    @php
        $jumlahHariIni = $pesananDikerjakan->filter(fn ($p) => $p->tanggal_kirim?->toDateString() === $hariIni)->count();
        $jumlahBesok = $pesananDikerjakan->filter(fn ($p) => $p->tanggal_kirim?->toDateString() === $besok)->count();
    @endphp

    <div class="card">
        <div class="card-head">
            <div>
                <h2 id="pesanan-dikerjakan-title">Pesanan yang Harus Dikerjakan</h2>
                <div class="sub-inline">Jadwal kirim hari ini &amp; besok yang belum selesai</div>
            </div>
            <div class="shortcut-row" aria-label="Shortcut pesanan">
                <a href="{{ url('/pesanan?tanggal=' . $hariIni) }}" class="btn btn-ghost btn-sm">Pesanan hari ini ({{ $jumlahHariIni }})</a>
                <a href="{{ url('/pesanan?tanggal=' . $besok) }}" class="btn btn-ghost btn-sm">Pesanan besok ({{ $jumlahBesok }})</a>
            </div>
        </div>

        <div class="table-card" tabindex="0" role="region" aria-labelledby="pesanan-dikerjakan-title">
            <table class="t-cards t-dashboard-todo">
                <thead>
                    <tr>
                        <th>Jadwal</th>
                        <th>No. Pesanan</th>
                        <th>Pemesan</th>
                        <th>Menu</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pesananDikerjakan as $p)
                        @php
                            $isHariIni = $p->tanggal_kirim?->toDateString() === $hariIni;
                            $cls = match($p->status->value) {
                                'menunggu_konfirmasi' => 't-wait',
                                'diproses' => 't-proc',
                                'diantar' => 't-send',
                                default => 't-done',
                            };
                            $nextAction = match($p->status->value) {
                                'menunggu_konfirmasi' => ['status' => 'diproses', 'label' => 'Proses'],
                                'diproses' => ['status' => 'diantar', 'label' => 'Antar'],
                                'diantar' => ['status' => 'selesai', 'label' => 'Selesai'],
                                default => null,
                            };
                            $ringkasanMenu = $p->detailPesanan
                                ->map(fn ($d) => $d->jumlah . '× ' . ($d->menu?->nama_menu ?? 'Menu'))
                                ->implode(', ');
                        @endphp
                        <tr>
                            <td class="nowrap" data-label="Jadwal">
                                <span class="tag {{ $isHariIni ? 't-unpaid' : 't-proc' }}">{{ $isHariIni ? 'Hari ini' : 'Besok' }}</span>
                                <div class="hint">{{ $p->tanggal_kirim?->translatedFormat('d M') }}</div>
                            </td>
                            <td class="no nowrap" data-label="No. Pesanan">
                                <a href="{{ url('/pesanan/' . $p->id) }}">{{ $p->no_pesanan }}</a>
                            </td>
                            <td class="col-pemesan" data-label="Pemesan">
                                {{ $p->nama_pemesan }}
                                <div class="hint">{{ $p->no_hp }}</div>
                            </td>
                            <td class="col-menu" data-label="Menu">
                                {{ $ringkasanMenu !== '' ? $ringkasanMenu : '—' }}
                                @if($p->catatan)
                                    <div class="hint">Catatan: {{ $p->catatan }}</div>
                                @endif
                            </td>
                            <td class="nowrap" data-label="Status">
                                <span class="tag {{ $cls }}"><span class="dot"></span>{{ $p->status->label() }}</span>
                            </td>
                            <td class="nowrap" data-label="Aksi">
                                <div class="rowbtns">
                                    @if($nextAction)
                                        <form method="POST" action="{{ url('/pesanan/' . $p->id . '/status') }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
                                            <button type="submit" class="btn btn-primary btn-sm">{{ $nextAction['label'] }}</button>
                                        </form>
                                    @endif
                                    <a href="{{ url('/pesanan/' . $p->id) }}" class="btn btn-ghost btn-sm">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Tidak ada pesanan yang harus dikerjakan untuk hari ini dan besok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
