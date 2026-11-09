<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('no_pesanan', 20)->unique();
            $table->string('nama_pemesan', 100);
            $table->string('no_hp', 15);
            $table->text('alamat_kirim');
            $table->date('tanggal_kirim');
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->enum('status', ['menunggu_konfirmasi', 'diproses', 'diantar', 'selesai'])
                  ->default('menunggu_konfirmasi');
            $table->enum('status_bayar', ['belum_lunas', 'lunas'])->default('belum_lunas');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('tanggal_kirim');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
