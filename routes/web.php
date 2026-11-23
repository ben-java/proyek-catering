<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

// Health check hanya dari loopback (monitoring lokal), bukan publik.
Route::get('/up', function () {
    abort_unless(in_array(request()->ip(), ['127.0.0.1', '::1'], true), 404);

    return response()->json(['status' => 'ok']);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::get('/pesan', [PesananController::class, 'createPublic'])->name('pesan.create');
Route::post('/pesan', [PesananController::class, 'storePublic'])->middleware('throttle:pesan-publik');
Route::get('/pesan/terima-kasih', [PesananController::class, 'terimaKasih']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('menu', MenuController::class)->except(['show']);
    Route::patch('menu/{menu}/tersedia', [MenuController::class, 'toggleTersedia']);

    Route::get('pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    // Sebelum route model binding {pesanan}, supaya /pesanan/export tidak dianggap ID.
    Route::get('pesanan/export', [PesananController::class, 'export'])->name('pesanan.export');
    Route::get('pesanan/create', [PesananController::class, 'create'])->name('pesanan.create');
    Route::post('pesanan', [PesananController::class, 'store']);
    Route::get('pesanan/{pesanan}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::post('pesanan/{pesanan}/status', [PesananController::class, 'changeStatus']);
    Route::post('pesanan/{pesanan}/bayar', [PesananController::class, 'toggleBayar']);
    Route::delete('pesanan/{pesanan}', [PesananController::class, 'destroy']);

    Route::get('laporan/porsi', [LaporanController::class, 'porsi'])->name('laporan.porsi');
    Route::get('laporan/pendapatan', [LaporanController::class, 'pendapatan'])->name('laporan.pendapatan');
});
