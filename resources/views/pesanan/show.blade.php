@extends('layouts.app')

@section('title', 'Detail Pesanan')
@section('content')
    @php
        $cls = match($pesanan->status->value){'menunggu_konfirmasi'=>'t-wait','diproses'=>'t-proc','diantar'=>'t-send','selesai'=>'t-done' default=>'t-done'};
        $order = ['menunggu_konfirmasi','diproses','diantar','selesai'];
        $idx = array_search($pesanan->status->value, $order);
    @endphp

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div style="font-size:20px;font-weight:800;color:var(--accent)">{{ $pesanan->no_pesanan }}</div>
            <div style="display:flex;gap:8px">
                <span class="tag {{ $cls }}"><span class="dot"></span>{{ $pesanan->status->label() }}</span>
                <span class="tag {{ $pesanan->status_bayar->value === 'lunas' ? 't-paid' : 't-unpaid' }}">{{ $pesanan->status_bayar->label() }}</span>
            </div>
        </div>

        <div class="info" style="display:grid;grid-template-columns:150px 1fr;gap:8px 14px;font-size:13.5px;margin-top:18px">
            <div style="color:var(--text-muted)">Pemesan</div><div style="font-weight:600">{{ $pesanan->nama_pemesan }}</div>
            <div style="color:var(--text-muted)">Nomor HP</div><div style="font-weight:600">{{ $pesanan->no_hp }}</div>
            <div style="color:var(--text-muted)">Alamat kirim</div><div style="font-weight:600">{{ $pesanan->alamat_kirim }}</div>
            <div style="color:var(--text-muted)">Tanggal kirim</div><div style="font-weight:600">{{ $pesanan->tanggal_kirim?->translatedFormat('d F Y') }}</div>
            <div style="color:var(--text-muted)">Status pembayaran</div><div style="font-weight:600">{{ $pesanan->status_bayar->label() }} — bayar setelah diantar</div>
            @if($pesanan->catatan)
                <div style="grid-column:1/-1;border-top:1px dashed var(--line);padding-top:10px;color:var(--text-muted);font-size:12.5px">Catatan: {{ $pesanan->catatan }}</div>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Rincian Menu</h2>
        <table>
            <thead><tr><th>Menu</th><th>Jumlah</th><th style="text-align:right">Harga Satuan</th><th style="text-align:right">Subtotal</th></tr></thead>
            <tbody>
                @foreach($pesanan->detailPesanan as $d)
                    <tr>
                        <td>{{ $d->menu->nama_menu }}</td>
                        <td>{{ $d->jumlah }}</td>
                        <td class="num">Rp{{ number_format($d->harga_satuan, 0, ',', '.') }}</td>
                        <td class="num">Rp{{ number_format($d->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;background:var(--surface-3);border-radius:var(--radius-sm);padding:14px 16px">
            <span style="color:var(--text-muted)">Total harga</span>
            <span style="font-size:22px;font-weight:800;color:var(--accent)">Rp{{ number_format($pesanan->total_harga, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="card">
        <h2>Ubah Status</h2>
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <form method="POST" action="{{ url('/pesanan/' . $pesanan->id . '/status') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                @csrf
                <div class="field" style="margin:0"><label>Status pesanan</label>
                    <select name="status">
                        @foreach(\App\Enums\StatusPesanan::cases() as $s)
                            <option value="{{ $s->value }}" @selected($pesanan->status === $s)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Simpan status</button>
            </form>
            <form method="POST" action="{{ url('/pesanan/' . $pesanan->id . '/bayar') }}">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">{{ $pesanan->status_bayar->value === 'lunas' ? 'Tandai belum lunas' : 'Tandai lunas' }}</button>
            </form>
            <form method="POST" action="{{ url('/pesanan/' . $pesanan->id) }}" onsubmit="return confirm('Hapus pesanan ini?')">
                @csrf @method('DELETE')
                <button class="btn btn-warn btn-sm" type="submit">Hapus</button>
            </form>
        </div>

        <div style="margin-top:20px">
            @foreach($order as $i => $s)
                <div style="display:flex;gap:12px;padding-bottom:14px;position:relative">
                    @if($i < count($order)-1)<div style="content:'';position:absolute;left:9px;top:22px;bottom:0;width:2px;background:var(--line-strong)"></div>@endif
                    <div style="width:20px;height:20px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;background:{{ $i <= $idx ? 'var(--accent)' : 'var(--line-strong)' }};color:#fff;font-size:11px">
                        {{ $i <= $idx ? '✓' : '' }}
                    </div>
                    <div style="font-size:13.5px">
                        {{ \App\Enums\StatusPesanan::from($s)->label() }}
                        <div style="font-size:11.5px;color:var(--text-muted)">{{ $i <= $idx ? 'tercapai' : 'belum' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
