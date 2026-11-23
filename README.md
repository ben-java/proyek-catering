# Aplikasi Pencatatan Pesanan Usaha Catering

Aplikasi web (PWA) berbasis **Laravel 13** untuk usaha catering rumahan: mencatat pesanan secara digital menggantikan buku tulis, menampilkan daftar pesanan harian, merekap porsi per menu (dasar belanja bahan), dan merekap pendapatan harian.

## Fitur

- Pencatatan pesanan digital menggantikan buku tulis, dengan nomor pesanan otomatis.
- Manajemen menu & ketersediaan (harian / nasi kotak / tumpeng).
- Rincian setiap pesanan dengan snapshot harga saat pemesanan (perubahan harga menu tidak mengubah pesanan lama).
- Rekap porsi per menu (dasar belanja bahan) dan rekap pendapatan harian.
- Status pesanan (menunggu konfirmasi → diproses → diantar → selesai) & status bayar (belum lunas / lunas).
- Hapus pesanan bersifat *soft delete* (`deleted_at`) — data tetap tersimpan untuk audit.
- Login admin (email/username) + pembatasan laju (rate limit) bawaan.
- PWA: dapat diinstal & bekerja offline (cache aset).

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

Login admin:

- Email / username: `admin`
- Password: `ADMIN_PASSWORD` yang diisi saat seeding. Seeder tidak lagi memakai
  password statis; kalau `ADMIN_PASSWORD` tidak diisi, password acak dibuat dan
  ditampilkan sekali di akhir `php artisan db:seed`.

```bash
ADMIN_PASSWORD='ganti-ini' php artisan migrate:fresh --seed
```

Akun yang sudah ada di database lama tetap memakai password lamanya — ganti
lewat `php artisan tinker` bila instalasi tersebut pernah online:

```php
\App\Models\User::where('email','admin')->update(['password' => bcrypt('password-baru-yang-kuat')]);
```

Ganti password admin (minimal 12 karakter, tanpa menulis password ke riwayat shell):

```bash
php artisan catering:admin-password
php artisan catering:admin-password 'PasswordBaruYangKuat'   # non-interaktif
```

## Testing

```bash
php artisan test
```

Suite PHPUnit memakai SQLite `:memory:` (tanpa perlu setup database terpisah). Cakupan:
- **Feature:** Auth, Menu, Pesanan, Laporan, Keamanan (security), regression (FixesRegressionTest), tabel porsi.
- **Unit:** Scalar.

## Sebelum online (checklist produksi)

- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`.
- `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` — hanya jika situs disajikan lewat **HTTPS**.
- `php.ini`: `expose_php=Off`; arahkan docroot ke folder `public/` (jangan ke root proyek).
- Aktifkan HTTPS + HSTS di server/web proxy (aplikasi hanya mengirim HSTS bila request sudah HTTPS).
- `php artisan migrate --force` (menambah kolom `deleted_at`: hapus pesanan bersifat *soft delete*, data tetap tersimpan untuk audit).
- Batas laju bawaan: login 5/menit per identifier + 30/menit per IP, formulir publik 6/menit + 60/hari per IP.

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

- Desain & keputusan UI yang disimpan di dalam repo: `06-ui/VISUAL-2026-11-22.md`.
- PRD, Requirements, Features, USER_FLOW, Database, API, Arch & ROADMAP dikelola di luar repo (folder docs lokal), sehingga tidak ikut ter-commit.

