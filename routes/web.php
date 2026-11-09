<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('menu', MenuController::class)->except(['show']);
    Route::patch('menu/{menu}/tersedia', [MenuController::class, 'toggleTersedia']);

    Route::get('pesanan', [PesananController::class, 'index'])->name('pesanan.index');
    Route::get('pesanan/create', [PesananController::class, 'create'])->name('pesanan.create');
    Route::post('pesanan', [PesananController::class, 'store']);
    Route::get('pesanan/{pesanan}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::post('pesanan/{pesanan}/status', [PesananController::class, 'changeStatus']);
    Route::post('pesanan/{pesanan}/bayar', [PesananController::class, 'toggleBayar']);
    Route::delete('pesanan/{pesanan}', [PesananController::class, 'destroy']);

    Route::get('laporan/porsi', [LaporanController::class, 'porsi'])->name('laporan.porsi');
    Route::get('laporan/pendapatan', [LaporanController::class, 'pendapatan'])->name('laporan.pendapatan');
});
