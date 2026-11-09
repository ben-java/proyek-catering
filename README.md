# Aplikasi Pencatatan Pesanan Usaha Catering

Aplikasi web (PWA) berbasis **Laravel 13** untuk usaha catering rumahan: mencatat pesanan secara digital menggantikan buku tulis, menampilkan daftar pesanan harian, merekap porsi per menu (dasar belanja bahan), dan merekap pendapatan harian.

## Persyaratan

- **Laravel Herd 1.30.0** (atau PHP ≥ 8.3) — Laravel 13 butuh PHP 8.3+.
- Composer (bundled oleh Herd).
- SQLite (default) atau MySQL.

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
# buka http://localhost:8000
```

Login demo admin:

- Email / username: `admin`
- Password: `password`

## Data contoh (Seeder)

- 1 akun admin
- 8 menu (harian, nasi kotak, tumpeng)
- 30 pesanan + rincian detail (total harga terhitung otomatis)

## Struktur domain (3 tabel utama)

| Tabel | Catatan |
|---|---|
| `menu` | nama_menu, jenis (harian/nasi_kotak/tumpeng), harga_satuan, tersedia |
| `pesanan` | no_pesanan (auto), nama_pemesan, no_hp, alamat_kirim, tanggal_kirim, total_harga, status, status_bayar, catatan |
| `detail_pesanan` | pesanan_id, menu_id, jumlah, harga_satuan (snapshot), subtotal |

- Status pesanan: `menunggu_konfirmasi → diproses → diantar → selesai`.
- Status bayar: `belum_lunas / lunas`.
- `harga_satuan` di `detail_pesanan` mencatat harga saat pesanan dibuat (perubahan harga menu tidak mengubah pesanan lama).
- Data pemesan disimpan langsung di `pesanan` (pelanggan memesan ulang → isi data diri lagi).

## Dokumen perencanaan

Lengkap di `C:\Users\AMMAR\Documents\proyek-catering\docs\` (PRD, Requirements, Features, USER_FLOW, Database, API, Arch, ROADMAP) + `docs/mockup/` (mockup & DESIGN.md).
