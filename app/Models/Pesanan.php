<?php

namespace App\Models;

use App\Enums\StatusBayar;
use App\Enums\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pesanan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesanan';

    protected $fillable = [
        'no_pesanan',
        'nama_pemesan',
        'no_hp',
        'alamat_kirim',
        'tanggal_kirim',
        'total_harga',
        'status',
        'status_bayar',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kirim' => 'date',
            'status' => StatusPesanan::class,
            'status_bayar' => StatusBayar::class,
            'total_harga' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pesanan) {
            $tanggal = now()->format('Ymd');
            $prefix = "PO-{$tanggal}-";

            // Ambil nomor urut tertinggi secara numerik, bukan leksikal, dan
            // kunci barisnya agar dua request paralel tidak memakai nomor sama.
            // substr($max, -3) lama salah untuk urutan >= 1000 ("...-1000" -> "000").
            // withTrashed(): nomor pesanan yang sudah dihapus tetap terpakai,
            // supaya unique index tidak bentrok saat nomor itu dibuat ulang.
            $urutan = self::withTrashed()
                ->where('no_pesanan', 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck('no_pesanan')
                ->map(fn (string $no) => (int) substr($no, strlen($prefix)))
                ->max() ?? 0;

            $pesanan->no_pesanan = $prefix.str_pad((string) ($urutan + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class);
    }

    public function scopeTanggal($query, $tanggal)
    {
        return $query->whereDate('tanggal_kirim', $tanggal);
    }
}
