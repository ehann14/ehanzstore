# EHANZSTORE.ID

Marketplace jual beli akun **Clash of Clans** berbasis web. Pembeli melihat katalog akun, lalu bertransaksi lewat WhatsApp. Admin mengelola stok akun, banner, dan penjualan lewat panel khusus.

> Dibangun dengan **Laravel 12** dan **PHP 8.2+**. Tampilan memakai Tailwind CSS dan Alpine.js (via CDN).

---

## Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi Lokal](#instalasi-lokal)
- [Konfigurasi `.env`](#konfigurasi-env)
- [Akun Super Admin Pertama](#akun-super-admin-pertama)
- [Peran Pengguna](#peran-pengguna)
- [Daftar Halaman & Route](#daftar-halaman--route)
- [Struktur Folder Penting](#struktur-folder-penting)
- [Upload Gambar](#upload-gambar)
- [Deploy ke Hosting](#deploy-ke-hosting)
- [Keamanan](#keamanan)
- [Perintah Berguna](#perintah-berguna)

---

## Fitur

**Halaman publik**
- Beranda dengan banner slider, akun terbaru, dan filter cepat Town Hall
- Katalog akun dengan pencarian (nama atau kode akun), filter TH, dan pengurutan harga atau terbaru
- Detail akun: foto, level hero, status proteksi, status email, harga, dan tombol **Beli via WhatsApp** dengan pesan otomatis
- Halaman **Cara Membeli** dan **Ketentuan Garansi 7 Hari** (`/garansi`)
- Pencatatan kunjungan pengunjung secara anonim (untuk analitik)
- Favicon dan logo toko di semua halaman

**Panel Admin** (`/admin`)
- Tambah, ubah, dan hapus akun COC (kode akun dibuat manual dan otomatis dirapikan)
- Upload banyak foto per akun, dengan foto utama dan urutan
- Status publikasi (`draft` atau `published`) dan status penjualan (`available`, `sold`, `archived`)
- Kelola banner slider beranda (aktif atau nonaktif, urutan, tautan WhatsApp/Instagram/URL bebas)

**Panel Super Admin** (`/super-admin`)
- Dashboard ringkasan toko
- Analitik pengunjung
- Pencatatan dan konfirmasi penjualan
- Kelola daftar admin

---

## Teknologi

| Bagian | Keterangan |
|---|---|
| Backend | Laravel 12, PHP ^8.2 |
| Database | MySQL / MariaDB (lihat catatan di bawah) |
| Frontend | Blade, Tailwind CSS (CDN), Alpine.js (CDN) |
| Auth | Session login bawaan Laravel dengan middleware role |
| Lainnya | Eloquent ORM, soft delete untuk akun, activity log |

> **Catatan database:** migrasi `update_email_status_on_coc_accounts` memakai perintah `ALTER TABLE ... MODIFY` khusus MySQL/MariaDB. Gunakan MySQL atau MariaDB, bukan SQLite, walaupun `.env.example` bawaan Laravel menyetel `DB_CONNECTION=sqlite`.

---

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru (ekstensi: `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `gd`)
- Composer 2
- MySQL 8 / MariaDB 10.6+
- Apache dengan `mod_rewrite` (atau Nginx dengan konfigurasi Laravel standar)
- Node.js **tidak wajib**, karena Tailwind dan Alpine dimuat lewat CDN

---

## Instalasi Lokal

```bash
# 1. Masuk ke folder proyek
cd ehanzstore

# 2. Pasang dependensi PHP
composer install

# 3. Salin file environment lalu buat app key
cp .env.example .env
php artisan key:generate

# 4. Atur database di .env (lihat bagian Konfigurasi), lalu jalankan migrasi
php artisan migrate

# 5. Buat akun super admin pertama
php artisan db:seed --class=SuperAdminSeeder

# 6. Jalankan server
php artisan serve
```

Buka `http://127.0.0.1:8000`.

> Jangan menjalankan `php artisan db:seed` tanpa `--class=SuperAdminSeeder`. `DatabaseSeeder` bawaan akan membuat user "Test User" yang tidak diperlukan.

---

## Konfigurasi `.env`

Bagian yang perlu kamu ubah dari `.env.example`:

```env
APP_NAME="EHANZSTORE.ID"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ehanzstore
DB_USERNAME=root
DB_PASSWORD=

# Kredensial super admin pertama (dibaca oleh SuperAdminSeeder)
SUPER_ADMIN_EMAIL=emailkamu@contoh.com
SUPER_ADMIN_PASSWORD=GantiDenganPasswordKuat
```

Nomor WhatsApp dan username Instagram toko dibaca dari tabel `store_settings` (key `whatsapp_number` dan `instagram_username`). Jika belum diisi, aplikasi memakai nilai bawaan yang ada di `AppServiceProvider`.

---

## Akun Super Admin Pertama

`SuperAdminSeeder` membuat satu akun dengan role `super_admin` memakai `SUPER_ADMIN_EMAIL` dan `SUPER_ADMIN_PASSWORD` dari `.env`.

- **Isi kedua variabel itu sebelum menjalankan seeder.** Jika kosong, seeder memakai nilai bawaan yang tertulis di kodenya, sehingga password awal bisa ditebak.
- Seeder tidak membuat ulang akun jika emailnya sudah ada.
- Login lewat `/login`.

---

## Peran Pengguna

| Role | Akses |
|---|---|
| `admin` | Panel `/admin`: kelola akun COC dan banner |
| `super_admin` | Semua akses admin ditambah panel `/super-admin`: dashboard, analitik, penjualan, kelola admin |

Akun dengan `is_active = false` otomatis dikeluarkan saat mencoba masuk.

---

## Daftar Halaman & Route

**Publik**

| URL | Nama route | Fungsi |
|---|---|---|
| `/` | `home` | Beranda |
| `/katalog` | `catalog` | Katalog akun |
| `/akun/{kode}` | `account.detail` | Detail akun |
| `/cara-membeli` | `how.to.buy` | Panduan pembelian |
| `/garansi` | `warranty` | Ketentuan garansi 7 hari |
| `/login` | `login` | Login admin |

**Admin** (prefix `/admin`, wajib login): `dashboard`, resource `accounts`, resource `banners`, serta aksi ubah status dan hapus foto.

**Super Admin** (prefix `/super-admin`, wajib login): `dashboard`, `analytics`, `sales`, `sales/confirm`, `admins`.

Lihat daftar lengkap dengan:

```bash
php artisan route:list
```

---

## Struktur Folder Penting

```
app/
├── Http/Controllers/
│   ├── PublicController.php        # halaman publik
│   ├── Auth/LoginController.php
│   ├── Admin/                      # AccountController, BannerController
│   └── SuperAdmin/DashboardController.php
├── Http/Middleware/                # RoleMiddleware, VisitorTracking
├── Models/                         # CocAccount, Banner, Sale, StoreSetting, dst.
├── Providers/AppServiceProvider.php  # membagikan data kontak toko ke view
└── Support/helpers.php             # helper upload_url()

resources/views/
├── layouts/                        # app, admin, dan partials (navbar, footer, favicon)
├── public/                         # home, catalog, detail, how-to-buy, warranty
├── admin/                          # accounts, banners
├── super-admin/                    # dashboard, analytics, sales, admins
└── auth/login.blade.php

public/
├── images/logo.png                 # logo toko (juga dipakai sebagai favicon)
└── uploads/                        # foto akun dan banner
```

---

## Upload Gambar

Foto akun dan banner disimpan langsung di folder publik `uploads` (`uploads/accounts/` dan `uploads/banners/`) lewat disk `uploads` (`config/filesystems.php`). **Tidak memakai symlink `storage`**, jadi aman di shared hosting / DirectAdmin.

- Path di database selalu relatif, contoh `accounts/xxx.jpg`. URL publik dibuat oleh helper `upload_url()` (atau `$image->url` / `$banner->image_url`) menjadi `/uploads/accounts/xxx.jpg`.
- Semua upload/hapus lewat class `App\Support\Uploads`. Jangan memakai `asset('storage/...')` atau `Storage::url()` untuk gambar.
- Lokasi folder: otomatis `<document root>/uploads`. Jika aplikasi dipisah dari `public_html`, isi `UPLOADS_PATH` di `.env` dengan path absolut ke folder `public_html/uploads`.
- Folder `accounts/` dan `banners/` serta `.htaccess` pelindung (tanpa eksekusi script, tanpa daftar folder) dibuat otomatis saat upload pertama.
- Foto lama yang masih ada di `storage/app/public/` tetap tampil: route fallback `/uploads/{path}` menyajikannya dan menyalinnya ke `uploads/` secara otomatis.

Pastikan folder berikut bisa ditulis oleh web server (755 sudah cukup jika PHP berjalan sebagai pemilik akun; jangan pakai 777):

```bash
chmod -R 755 public/uploads        # atau public_html/uploads
chmod -R 775 storage bootstrap/cache
```

---

## Deploy ke Hosting

1. Upload semua file proyek, lalu arahkan document root ke folder `public/`.
   Di shared hosting (cPanel), biasanya isi `public/` dipindah ke `public_html/` dan `index.php` disesuaikan path-nya ke folder `vendor` dan `bootstrap`.
2. Buat database MySQL dan isi `.env` produksi:
```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainkamu.com
```
3. Jalankan:
```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --class=SuperAdminSeeder
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
```
4. Pastikan `mod_rewrite` aktif agar `.htaccess` bekerja.

---

## Keamanan

- **Jangan** commit atau membagikan file `.env`. Isinya berisi `APP_KEY` dan kredensial database. Jika file itu pernah terkirim ke pihak lain, ganti password database dan buat ulang `APP_KEY`.
- Set `APP_DEBUG=false` di produksi.
- Ganti password super admin setelah login pertama.
- Folder `vendor/`, `.env`, dan `storage/logs` jangan pernah bisa diakses dari browser.
- Hanya beri role `super_admin` kepada orang yang benar-benar dipercaya.

---

## Perintah Berguna

```bash
php artisan serve              # jalankan server lokal
php artisan migrate:fresh      # reset database (hapus semua data!)
php artisan route:list         # lihat semua route
php artisan view:clear         # bersihkan cache tampilan
php artisan route:clear        # bersihkan cache route
php artisan config:clear       # bersihkan cache konfigurasi
php artisan optimize:clear     # bersihkan semua cache sekaligus
```

---

## Lisensi

Proyek privat milik EHANZSTORE.ID. Dibangun di atas [Laravel](https://laravel.com) yang berlisensi MIT.