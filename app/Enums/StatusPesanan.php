<?php

namespace App\Enums;

enum StatusPesanan: string
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Diproses = 'diproses';
    case Diantar = 'diantar';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu konfirmasi',
            self::Diproses => 'Diproses',
            self::Diantar => 'Diantar',
            self::Selesai => 'Selesai',
        };
    }
}
