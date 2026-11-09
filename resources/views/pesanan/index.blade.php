@extends('layouts.app')

@section('title', 'Pesanan')
@section('content')
    <div class="pagehead">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <h1>Daftar Pesanan Harian</h1>
                <div class="sub">Semua pesanan pada tanggal terpilih, dalam satu halaman</div>
            </div>
            <a href="{{ url('/pesanan/create') }}" class="btn btn-primary">+ Buat pesanan</a>
        </div>
    </div>

    <form class="filterbar" method="GET" action="{{ url('/pesanan') }}">
        <div class="field"><label>Tanggal kirim</label>
            <input type="date" name="tanggal" value="{{ request('tanggal') }}">
        </div>
        <div class="field"><label>Status</label>
            <select name="status">
                <option value="">Semua status</option>
                @foreach(\App\Enums\StatusPesanan::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="flex:1;min-width:180px"><label>Cari</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nama pemesan / nomor pesanan…" style="width:100%">
        </div>
        <button class="btn btn-ghost" type="submit">Terapkan</button>
    </form>

    <div class="table-card">
        <table class="t-cards">
            <thead><tr><th>No. Pesanan</th><th>Pemesan</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Bayar</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($pesanan as $p)
                    <tr>
                        <td class="no" data-label="No. Pesanan">{{ $p->no_pesanan }}</td>
                        <td data-label="Pemesan">{{ $p->nama_pemesan }}<div class="hint">{{ $p->no_hp }}</div></td>
                        <td class="nowrap" data-label="Tanggal">{{ $p->tanggal_kirim?->translatedFormat('d M') }}</td>
                        <td class="num" data-label="Total">Rp{{ number_format($p->total_harga, 0, ',', '.') }}</td>
                        <td>
                            @php $cls = match($p->status->value){'menunggu_konfirmasi'=>'t-wait','diproses'=>'t-proc','diantar'=>'t-send','selesai'=>'t-done', default=>'t-done'}; @endphp
                            <span class="tag {{ $cls }}"><span class="dot"></span>{{ $p->status->label() }}</span>
                        </td>
                        <td>
                            <span class="tag {{ $p->status_bayar->value === 'lunas' ? 't-paid' : 't-unpaid' }}">{{ $p->status_bayar->label() }}</span>
                        </td>
                        <td data-label="Aksi">
                            <div class="rowbtns">
                                <a href="{{ url('/pesanan/' . $p->id) }}" class="btn btn-ghost btn-sm">Lihat</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada pesanan untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt">{{ $pesanan->links() }}</div>
    </div>
@endsection
