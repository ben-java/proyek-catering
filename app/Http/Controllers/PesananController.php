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
        // Query string apa pun bisa berbentuk array (?tanggal[]=x). Paksa skalar
        // supaya tidak sampai ke query builder / view sebagai array.
        $tanggal = \App\Support\Scalar::str($request->query('tanggal'));
        $status = \App\Support\Scalar::str($request->query('status'));
        $cari = \App\Support\Scalar::str($request->query('q'));

        // Batas atas halaman: 20.000 baris dalam satu respons butuh ~118 MB
        // (melebihi memory_limit 128M) -> 500. 200/halaman cukup untuk rekap harian.
        $perPage = $tanggal !== '' ? 200 : 20;

        $pesanan = Pesanan::query()
            ->with('detailPesanan.menu')
            ->when($tanggal !== '', fn ($q) => $q->whereDate('tanggal_kirim', $tanggal))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($cari !== '', function ($q) use ($cari) {
                // BUG-09: kelompokkan OR di dalam AND tanggal/status.
                $q->where(function ($qq) use ($cari) {
                    $qq->where('nama_pemesan', 'like', "%{$cari}%")
                        ->orWhere('no_pesanan', 'like', "%{$cari}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)->withQueryString();

        return view('pesanan.index', compact('pesanan', 'tanggal', 'status', 'cari'));
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
            'alamat_kirim' => ['required', 'string', 'max:500'],
            // BUG-07: minimal besok (batas eksplisit, bukan "today+1")
            'tanggal_kirim' => ['required', 'date', function ($attr, $value, $fail) {
                $tanggal = Carbon::parse($value);
                if ($tanggal->lt(now()->addDay()->startOfDay())) {
                    $fail('Tanggal kirim minimal besok.');
                }
                // Tanpa batas atas, pesanan bisa bertanggal 9999-12-31 dan
                // merusak rekap pendapatan/porsi.
                if ($tanggal->gt(now()->addYear())) {
                    $fail('Tanggal kirim maksimal satu tahun ke depan.');
                }
            }],
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            // BUG-08: menu harus aktif pada saat validasi
            'items.*.menu_id' => ['required', 'integer', function ($attr, $value, $fail) {
                if (! Menu::whereKey($value)->where('tersedia', true)->exists()) {
                    $fail('Menu tidak tersedia.');
                }
            }],
            // Batas atas menjaga total_harga tetap di dalam decimal(12,2).
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:200'],
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

    /**
     * Ekspor rekap harian ke CSV. Memakai chunk + cursor tulis langsung ke
     * output, jadi ribuan pesanan tidak dimuat sekaligus ke memori.
     */
    public function export(Request $request)
    {
        $tanggal = \App\Support\Scalar::str($request->query('tanggal'));
        $tanggal = $tanggal !== '' ? Carbon::parse($tanggal)->toDateString() : now()->toDateString();

        // Cegah CSV formula injection: nilai yang diawali = + - @ diberi prefiks '.
        $bersihkan = function ($nilai) {
            $nilai = (string) $nilai;

            return preg_match('/^[=+\-@]/', $nilai) ? "'".$nilai : $nilai;
        };

        return response()->streamDownload(function () use ($tanggal, $bersihkan) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'No. Pesanan', 'Pemesan', 'No HP', 'Alamat', 'Tanggal Kirim', 'Catatan',
                'Menu', 'Jumlah', 'Harga Satuan', 'Subtotal', 'Total Pesanan',
                'Status', 'Status Bayar',
            ]);

            Pesanan::query()
                ->with('detailPesanan.menu')
                ->whereDate('tanggal_kirim', $tanggal)
                ->orderBy('no_pesanan')
                ->chunk(200, function ($daftar) use ($out, $bersihkan) {
                    foreach ($daftar as $pesanan) {
                        foreach ($pesanan->detailPesanan as $d) {
                            fputcsv($out, [
                                $bersihkan($pesanan->no_pesanan),
                                $bersihkan($pesanan->nama_pemesan),
                                $bersihkan($pesanan->no_hp),
                                $bersihkan($pesanan->alamat_kirim),
                                $pesanan->tanggal_kirim?->toDateString(),
                                $bersihkan((string) $pesanan->catatan),
                                $bersihkan($d->menu?->nama_menu),
                                $d->jumlah,
                                $d->harga_satuan,
                                $d->subtotal,
                                $pesanan->total_harga,
                                $pesanan->status->value,
                                $pesanan->status_bayar->value,
                            ]);
                        }
                    }
                });

            fclose($out);
        }, "pesanan-{$tanggal}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function changeStatus(Request $request, Pesanan $pesanan)
    {
        $data = $request->validate([
            'status' => ['required', 'in:menunggu_konfirmasi,diproses,diantar,selesai'],
        ]);

        // Alur maju saja: mencegah lompatan menunggu_konfirmasi -> selesai
        // (melewati produksi/pengantaran) yang mengacaukan rekap operasional.
        $alur = [
            'menunggu_konfirmasi' => ['diproses'],
            'diproses' => ['diantar', 'selesai'],
            'diantar' => ['selesai'],
            'selesai' => [],
        ];
        $sekarang = $pesanan->status->value;
        $tujuan = $data['status'];

        if ($tujuan !== $sekarang && ! in_array($tujuan, $alur[$sekarang] ?? [], true)) {
            return back()->with('err', "Status tidak bisa berubah dari {$sekarang} ke {$tujuan}.");
        }

        $pesanan->update(['status' => $tujuan]);
        \Illuminate\Support\Facades\Log::info('pesanan.status', [
            'id' => $pesanan->id,
            'no_pesanan' => $pesanan->no_pesanan,
            'dari' => $sekarang,
            'ke' => $tujuan,
            'oleh' => $request->user()?->email,
        ]);

        return back()->with('ok', 'Status pesanan diperbarui.');
    }

    public function toggleBayar(Request $request, Pesanan $pesanan)
    {
        $lama = $pesanan->status_bayar;
        $menjadiLunas = $lama !== StatusBayar::Lunas;

        // Tandai lunas = klaim uang sudah diterima. Wajib konfirmasi eksplisit
        // untuk pesanan bernilai uang, supaya tidak berubah karena salah klik.
        if ($menjadiLunas && (float) $pesanan->total_harga > 0 && ! $request->boolean('konfirmasi')) {
            return back()->with('err', 'Centang konfirmasi pembayaran sebelum menandai lunas.');
        }

        $pesanan->update([
            'status_bayar' => $menjadiLunas ? StatusBayar::Lunas : StatusBayar::BelumLunas,
        ]);

        \Illuminate\Support\Facades\Log::info('pesanan.status_bayar', [
            'id' => $pesanan->id,
            'no_pesanan' => $pesanan->no_pesanan,
            'dari' => $lama->value,
            'ke' => $pesanan->status_bayar->value,
            'oleh' => $request->user()?->email,
        ]);

        return back()->with('ok', 'Status pembayaran diperbarui.');
    }

    public function destroy(Request $request, Pesanan $pesanan)
    {
        \Illuminate\Support\Facades\Log::warning('pesanan.hapus', [
            'id' => $pesanan->id,
            'no_pesanan' => $pesanan->no_pesanan,
            'pemesan' => $pesanan->nama_pemesan,
            'total_harga' => $pesanan->total_harga,
            'oleh' => $request->user()?->email,
        ]);

        $pesanan->delete();

        return redirect('/pesanan')->with('ok', 'Pesanan dihapus.');
    }
}
