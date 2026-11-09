@extends('layouts.app')

@section('title', 'Edit Menu')
@section('content')
    <div class="pagehead">
        <h1>Edit Menu</h1>
        <div class="sub">Perbarui {{ $menu->nama_menu }}</div>
    </div>

    <div class="card" style="max-width:640px">
        <form method="POST" action="{{ url('/menu/' . $menu->id) }}">
            @csrf @method('PUT')
            <div class="field"><label>Nama menu *</label><input type="text" name="nama_menu" value="{{ old('nama_menu', $menu->nama_menu) }}" required></div>
            <div class="field"><label>Jenis *</label>
                <select name="jenis" required>
                    <option value="harian" @selected(old('jenis', $menu->jenis) === 'harian')>Harian</option>
                    <option value="nasi_kotak" @selected(old('jenis', $menu->jenis) === 'nasi_kotak')>Nasi kotak</option>
                    <option value="tumpeng" @selected(old('jenis', $menu->jenis) === 'tumpeng')>Tumpeng</option>
                </select>
            </div>
            <div class="field"><label>Harga satuan (Rp) *</label><input type="number" name="harga_satuan" min="0" step="500" value="{{ old('harga_satuan', $menu->harga_satuan) }}" required></div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--text-secondary);margin-bottom:18px">
                <input type="checkbox" name="tersedia" value="1" @checked($menu->tersedia) style="width:auto"> Tersedia untuk dipesan
            </label>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ url('/menu') }}" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
@endsection
