<?php

namespace App\Models;

use App\Enums\StatusBayar;
use App\Enums\StatusPesanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Pesanan extends Model
{
    use HasFactory;

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
            $max = self::query()
                ->where('no_pesanan', 'like', $prefix.'%')
                ->value(DB::raw('MAX(no_pesanan)'));
            $urutan = $max ? ((int) substr($max, -3)) + 1 : 1;
            $pesanan->no_pesanan = $prefix.str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
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
