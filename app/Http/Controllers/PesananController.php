<?php

namespace App\Http\Controllers;

use App\Enums\StatusBayar;
use App\Enums\StatusPesanan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $pesanan = Pesanan::query()
            ->with('detailPesanan.menu')
            ->when($request->tanggal, fn ($q, $d) => $q->whereDate('tanggal_kirim', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, function ($q, $c) {
                $q->where('nama_pemesan', 'like', "%{$c}%")
                  ->orWhere('no_pesanan', 'like', "%{$c}%");
            })
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('pesanan.index', compact('pesanan'));
    }

    public function create()
    {
        $menu = Menu::where('tersedia', true)->orderBy('nama_menu')->get();

        return view('pesanan.create', compact('menu'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_pemesan' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'max:15'],
            'alamat_kirim' => ['required', 'string'],
            'tanggal_kirim' => ['required', 'date', 'after_or_equal:today+1'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'exists:menu,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $pesanan = Pesanan::create([
            'nama_pemesan' => $data['nama_pemesan'],
            'no_hp' => $data['no_hp'],
            'alamat_kirim' => $data['alamat_kirim'],
            'tanggal_kirim' => $data['tanggal_kirim'],
            'catatan' => $data['catatan'] ?? null,
            'status' => StatusPesanan::MenungguKonfirmasi,
            'status_bayar' => StatusBayar::BelumLunas,
            'total_harga' => 0,
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $menu = Menu::findOrFail($item['menu_id']);
            $subtotal = (float) $menu->harga_satuan * $item['jumlah'];
            $total += $subtotal;
            DetailPesanan::create([
                'pesanan_id' => $pesanan->id,
                'menu_id' => $menu->id,
                'jumlah' => $item['jumlah'],
                'harga_satuan' => $menu->harga_satuan,
                'subtotal' => $subtotal,
            ]);
        }
        $pesanan->update(['total_harga' => $total]);

        return redirect('/pesanan')->with('ok', "Pesanan {$pesanan->no_pesanan} berhasil dibuat (menunggu konfirmasi).");
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load('detailPesanan.menu');

        return view('pesanan.show', compact('pesanan'));
    }

    public function changeStatus(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'status' => ['required', 'in:menunggu_konfirmasi,diproses,diantar,selesai'],
        ]);
        $pesanan->update(['status' => $data['status']]);

        return back()->with('ok', 'Status pesanan diperbarui.');
    }

    public function toggleBayar(Pesanan $pesanan)
    {
        $pesanan->update([
            'status_bayar' => $pesanan->status_bayar === StatusBayar::Lunas ? StatusBayar::BelumLunas : StatusBayar::Lunas,
        ]);

        return back()->with('ok', 'Status pembayaran diperbarui.');
    }

    public function destroy(Pesanan $pesanan)
    {
        $pesanan->delete();

        return redirect('/pesanan')->with('ok', 'Pesanan dihapus.');
    }
}
