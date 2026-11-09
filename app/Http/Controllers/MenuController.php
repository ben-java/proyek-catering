<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menu = Menu::query()
            ->when($request->jenis, fn ($q, $j) => $q->where('jenis', $j))
            ->when($request->filled('tersedia'), fn ($q) => $q->where('tersedia', $request->boolean('tersedia')))
            ->when($request->q, fn ($q, $c) => $q->where('nama_menu', 'like', "%{$c}%"))
            ->orderBy('nama_menu')
            ->paginate(20)->withQueryString();

        return view('menu.index', compact('menu'));
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
            'harga_satuan' => ['required', 'numeric', 'min:0'],
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
            'harga_satuan' => ['required', 'numeric', 'min:0'],
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
