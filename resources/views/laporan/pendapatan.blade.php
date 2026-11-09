@extends('layouts.app')

@section('title', 'Rekap Pendapatan')
@section('content')
    <div class="pagehead">
        <h1>Rekap Pendapatan Harian</h1>
        <div class="sub">Dihitung dari status pembayaran pesanan (pengganti mutasi saldo rekening)</div>
    </div>

    <form class="filterbar" method="GET" action="{{ url('/laporan/pendapatan') }}">
        <div class="field"><label>Tanggal</label>
            <input type="date" name="tanggal" value="{{ $tanggal }}">
        </div>
        <button class="btn btn-ghost" type="submit">Tampilkan</button>
        <a href="{{ url('/laporan/porsi?tanggal=' . $tanggal) }}" class="btn btn-ghost">Lihat rekap porsi</a>
    </form>

    <div class="stats">
        <div class="stat"><div class="lbl">Total pendapatan (lunas)</div><div class="num">Rp{{ number_format($statistik['total_pendapatan'], 0, ',', '.') }}</div></div>
        <div class="stat"><div class="lbl">Pesanan belum lunas</div><div class="num warn">{{ $statistik['belum_lunas'] }}</div></div>
        <div class="stat"><div class="lbl">Total pesanan</div><div class="num">{{ $statistik['total_pesanan'] }}</div></div>
    </div>

    <div class="table-card">
        <table>
            <thead><tr><th>No. Pesanan</th><th>Pemesan</th><th>Status</th><th style="text-align:right">Total</th><th>Bayar</th></tr></thead>
            <tbody>
                @forelse($daftar as $p)
                    <tr>
                        <td class="no">{{ $p->no_pesanan }}</td>
                        <td>{{ $p->nama_pemesan }}</td>
                        <td>
                            @php $cls = match($p->status->value){'menunggu_konfirmasi'=>'t-wait','diproses'=>'t-proc','diantar'=>'t-send','selesai'=>'t-done', default=>'t-done'}; @endphp
                            <span class="tag {{ $cls }}">{{ $p->status->label() }}</span>
                        </td>
                        <td class="num">Rp{{ number_format($p->total_harga, 0, ',', '.') }}</td>
                        <td><span class="tag {{ $p->status_bayar->value === 'lunas' ? 't-paid' : 't-unpaid' }}">{{ $p->status_bayar->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada pesanan pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
