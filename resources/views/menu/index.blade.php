@extends('layouts.app')

@section('title', 'Menu')
@section('content')
    <div class="pagehead">
        <h1>Pengelolaan Menu</h1>
        <div class="sub">Tambah, ubah, dan nonaktifkan menu yang ditawarkan</div>
    </div>

    <form class="filterbar" method="GET" action="{{ url('/menu') }}">
        <div class="field"><label for="f-jenis">Jenis</label>
            <select id="f-jenis" name="jenis">
                <option value="">Semua</option>
                @foreach(['harian'=>'Harian','nasi_kotak'=>'Nasi kotak','tumpeng'=>'Tumpeng'] as $v=>$lbl)
                    <option value="{{ $v }}" @selected($jenis === $v)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="f-tersedia">Tersedia</label>
            <select id="f-tersedia" name="tersedia">
                <option value="">Semua</option>
                <option value="1" @selected($tersedia === '1')>Ya</option>
                <option value="0" @selected($tersedia === '0')>Tidak</option>
            </select>
        </div>
        <div class="field" style="flex:1;min-width:200px"><label for="f-cari">Cari</label>
            <input id="f-cari" type="text" name="q" value="{{ $cari }}" placeholder="nama menu…" style="width:100%">
        </div>
        <button class="btn btn-ghost" type="submit">Terapkan</button>
        <a href="{{ url('/menu/create') }}" class="btn btn-primary">+ Tambah menu</a>
    </form>

    <div class="table-card">
        <table class="t-cards">
            <thead><tr><th>Menu</th><th>Jenis</th><th>Harga</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($menu as $m)
                    <tr>
                        <td data-label="Menu"><strong>{{ $m->nama_menu }}</strong></td>
                        <td data-label="Jenis">{{ ucwords(str_replace('_',' ',$m->jenis)) }}</td>
                        <td data-label="Harga">Rp{{ number_format($m->harga_satuan, 0, ',', '.') }}</td>
                        <td data-label="Status">
                            @if($m->tersedia)
                                <span class="tag t-paid"><span class="dot"></span>Tersedia</span>
                            @else
                                <span class="tag t-done"><span class="dot"></span>Nonaktif</span>
                            @endif
                        </td>
                        <td data-label="Aksi">
                            <div class="rowbtns">
                                <a href="{{ url('/menu/' . $m->id . '/edit') }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form method="POST" action="{{ url('/menu/' . $m->id . '/tersedia') }}" style="margin:0">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-sm" type="submit">{{ $m->tersedia ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <form method="POST" action="{{ url('/menu/' . $m->id) }}" style="margin:0" data-confirm="Hapus menu ini?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-warn btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada menu.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt">{{ $menu->links() }}</div>
    </div>
@endsection
