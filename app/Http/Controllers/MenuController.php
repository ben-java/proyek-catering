<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        // Paksa skalar: ?q[]=x / ?jenis[]=y sebelumnya memicu TypeError 500.
        $jenis = \App\Support\Scalar::str($request->query('jenis'));
        $cari = \App\Support\Scalar::str($request->query('q'));
        $tersediaRaw = $request->query('tersedia');
        $tersedia = is_scalar($tersediaRaw) ? (string) $tersediaRaw : '';

        $menu = Menu::query()
            ->when($jenis !== '', fn ($q) => $q->where('jenis', $jenis))
            ->when($tersedia !== '', fn ($q) => $q->where('tersedia', filter_var($tersedia, FILTER_VALIDATE_BOOLEAN)))
            ->when($cari !== '', fn ($q) => $q->where('nama_menu', 'like', "%{$cari}%"))
            ->orderBy('nama_menu')
            ->paginate(20)->withQueryString();

        return view('menu.index', compact('menu', 'jenis', 'cari', 'tersedia'));
    }

    public function create()
    {
        return view('menu.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_menu' => ['required', 'string', 'max:100'],
            'jenis' => ['required', 'in:harian,nasi_kotak,tumpeng'],
            // Batasi ke rentang decimal(10,2) kolom DB; tanpa ini nilai 1e309
            // tersimpan sebagai INF dan 1e20 melewati kapasitas kolom.
            'harga_satuan' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'tersedia' => ['sometimes', 'boolean'],
        ]);
        $data['tersedia'] = $request->boolean('tersedia');

        Menu::create($data);

        return redirect('/menu')->with('ok', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        return view('menu.edit', compact('menu'));
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'nama_menu' => ['required', 'string', 'max:100'],
            'jenis' => ['required', 'in:harian,nasi_kotak,tumpeng'],
            // Batasi ke rentang decimal(10,2) kolom DB; tanpa ini nilai 1e309
            // tersimpan sebagai INF dan 1e20 melewati kapasitas kolom.
            'harga_satuan' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'tersedia' => ['sometimes', 'boolean'],
        ]);
        $data['tersedia'] = $request->boolean('tersedia');

        $menu->update($data);

        return redirect('/menu')->with('ok', 'Menu berhasil diperbarui.');
    }

    public function toggleTersedia(Menu $menu)
    {
        $menu->update(['tersedia' => ! $menu->tersedia]);

        return back()->with('ok', 'Status ketersediaan menu diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        if ($menu->detailPesanan()->exists()) {
            return back()->with('err', 'Menu masih dipakai pada pesanan, tidak dapat dihapus.');
        }
        $menu->delete();

        return back()->with('ok', 'Menu dihapus.');
    }
}
