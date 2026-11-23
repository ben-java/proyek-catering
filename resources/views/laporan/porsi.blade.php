@extends('layouts.app')

@section('title', 'Rekap Porsi')
@section('content')
    <div class="pagehead">
        <h1>Rekap Porsi per Menu</h1>
        <div class="sub">Dasar perencanaan belanja bahan</div>
    </div>

    <form class="filterbar" method="GET" action="{{ url('/laporan/porsi') }}">
        <div class="field"><label for="f-tanggal">Tanggal kirim</label>
            <input id="f-tanggal" type="date" name="tanggal" value="{{ $tanggal }}">
        </div>
        <button class="btn btn-ghost" type="submit">Tampilkan</button>
        <button class="btn btn-primary no-print" type="button" id="cetakLaporan">Cetak</button>
        <a href="{{ url('/laporan/pendapatan?tanggal=' . $tanggal) }}" class="btn btn-ghost">Lihat rekap pendapatan</a>
    </form>

    <div class="stats">
        <div class="stat"><div class="lbl">Tanggal</div><div class="num" style="font-size:18px">{{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('d M Y') }}</div></div>
        <div class="stat"><div class="lbl">Total porsi terjual</div><div class="num">{{ $totalPorsi }}</div></div>
        <div class="stat"><div class="lbl">Jumlah jenis menu</div><div class="num">{{ $rekap->count() }}</div></div>
    </div>

    <div class="table-card">
        <table>
            <thead><tr><th>Menu</th><th>Jenis</th><th style="text-align:right">Total Porsi</th></tr></thead>
            <tbody>
                @forelse($rekap as $r)
                    <tr>
                        <td><strong>{{ $r->nama_menu }}</strong></td>
                        <td>{{ ucwords(str_replace('_',' ',$r->jenis)) }}</td>
                        <td class="num">{{ $r->total_porsi }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Tidak ada pesanan pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        document.getElementById('cetakLaporan')?.addEventListener('click', () => window.print());
    </script>
@endpush
