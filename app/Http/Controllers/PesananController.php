<?php

namespace App\Http\Controllers;

use App\Enums\StatusBayar;
use App\Enums\StatusPesanan;
use App\Models\DetailPesanan;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        // BUG-18: saat tanggal dipilih, seluruh pesanan hari itu tampil satu halaman.
        $perPage = $request->filled('tanggal') ? 100000 : 20;

        $pesanan = Pesanan::query()
            ->with('detailPesanan.menu')
            ->when($request->tanggal, fn ($q, $d) => $q->whereDate('tanggal_kirim', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, function ($q, $c) {
                // BUG-09: kelompokkan OR di dalam AND tanggal/status.
                $q->where(function ($qq) use ($c) {
                    $qq->where('nama_pemesan', 'like', "%{$c}%")
                        ->orWhere('no_pesanan', 'like', "%{$c}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)->withQueryString();

        return view('pesanan.index', compact('pesanan'));
    }

    public function create()
    {
        return $this->createView('/pesanan');
    }

    public function createPublic()
    {
        return $this->createView('/pesan');
    }

    private function createView(string $formAction)
    {
        $menu = Menu::where('tersedia', true)->orderBy('nama_menu')->get();

        return view('pesanan.create', compact('menu', 'formAction'));
    }

    public function store(Request $request)
    {
        $pesanan = $this->persist($request);

        return redirect('/pesanan')->with('ok', "Pesanan {$pesanan->no_pesanan} berhasil dibuat (menunggu konfirmasi).");
    }

    public function storePublic(Request $request)
    {
        $pesanan = $this->persist($request);

        return redirect('/pesan/terima-kasih')->with('ok', "Pesanan {$pesanan->no_pesanan} diterima. Kami akan menghubungi Anda.");
    }

    public function terimaKasih()
    {
        return view('pesanan.terima-kasih');
    }

    private function persist(Request $request): Pesanan
    {
        $data = $request->validate([
            'nama_pemesan' => ['required', 'string', 'max:100'],
            // BUG-16: tolak huruf pada nomor HP
            'no_hp' => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'alamat_kirim' => ['required', 'string'],
            // BUG-07: minimal besok (batas eksplisit, bukan "today+1")
            'tanggal_kirim' => ['required', 'date', function ($attr, $value, $fail) {
                if (Carbon::parse($value)->lt(now()->addDay()->startOfDay())) {
                    $fail('Tanggal kirim minimal besok.');
                }
            }],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            // BUG-08: menu harus aktif pada saat validasi
            'items.*.menu_id' => ['required', function ($attr, $value, $fail) {
                if (! Menu::whereKey($value)->where('tersedia', true)->exists()) {
                    $fail('Menu tidak tersedia.');
                }
            }],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ]);

        // BUG-05: transaksi agar pembuatan pesanan atomik.
        // BUG-04: retry penomoran jika terjadi bentrok (konkuren).
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
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
                    foreach ($data['items'] as $item) {
                        $menu = Menu::whereKey($item['menu_id'])->where('tersedia', true)->firstOrFail();
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

                    return $pesanan;
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) {
                    throw $e;
                }
            } catch (QueryException $e) {
                throw $e;
            }
        }

        throw new \RuntimeException('Tidak dapat menyimpan pesanan.');
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
