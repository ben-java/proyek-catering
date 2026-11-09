<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';

    protected $fillable = ['nama_menu', 'jenis', 'harga_satuan', 'tersedia'];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'tersedia' => 'boolean',
        ];
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(DetailPesanan::class);
    }
}
