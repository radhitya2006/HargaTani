# HargaTani

Fondasi proyek UAS HargaTani berdasarkan langkah 2 pada [PRD](PRD.md). Menggunakan Laravel 13, Livewire 4, Blade, Tailwind CSS, dan MySQL dalam satu aplikasi.

## Status implementasi

- Starter kit Livewire resmi, login/logout, registrasi, pengaturan profil dan kata sandi.
- Registrasi hanya menerima `petani` atau `pembeli`; `admin` dibuat melalui seeder.
- Enum peran, migration peran/status aktif, middleware akses peran dan akun aktif, serta UserPolicy untuk akses profil.
- Dashboard awal masing-masing peran. Harga pasar, kalkulator, etalase, pesanan, dan Gemini belum diimplementasikan.
- Session, cache, dan queue menggunakan database. Email lokal ditulis ke log; verifikasi email, 2FA, dan passkeys belum diaktifkan.
- Git sudah diinisialisasi sebelum pengerjaan ini. Tidak ada commit atau push otomatis.

## Kebutuhan lingkungan

Lingkungan yang dipakai saat implementasi: PHP 8.3.36, Composer 2.10.3, Node.js 24.19.0, MySQL 8.0.30, Laravel 13.42.0, dan Livewire 4.4.8. PHP harus memiliki ekstensi Laravel dan `pdo_mysql`. Versi paket dikunci melalui `composer.lock` dan `package-lock.json`; gunakan keduanya saat instalasi ulang.

Starter berasal dari `laravel/livewire-starter-kit`, commit `52bce9a263792421c6a44353efc6ba87130d5ec3`, lalu disesuaikan untuk HargaTani. Starter menggunakan Vite Plus (`vp`) sebagai tooling Vite.

## Setup di Laragon / Windows

1. Aktifkan MySQL pada Laragon dan buka terminal di root proyek.
2. Jalankan `composer install` dan `npm.cmd ci`.
3. Jika belum ada `.env`, salin `.env.example` menjadi `.env`. Jangan menimpa `.env` yang sudah berisi konfigurasi lokal.
4. Isi koneksi database, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` (minimal 12 karakter). Tidak ada password admin bawaan di repository.
5. Buat database melalui HeidiSQL atau MySQL:

```sql
CREATE DATABASE IF NOT EXISTS hargatani CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS hargatani_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

6. Jalankan perintah berikut untuk instalasi baru:

```powershell
php artisan key:generate
php artisan migrate --seed
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000`. Untuk pengembangan dengan pembaruan aset otomatis, jalankan `npm.cmd run dev` pada terminal kedua. Server PHP dan MySQL tetap harus berjalan.

Alternatif virtual host Laragon: arahkan document root `hargatani.test` ke direktori `public` proyek ini dan sesuaikan `APP_URL`. Folder proyek boleh tetap di `C:\1.UNUD\Semester 3\HargaTani`; tidak perlu dipindah ke `www`. Virtual host tidak otomatis dikonfigurasi oleh implementasi ini.

### Akun admin lokal

Pada workspace ini database `hargatani` dan akun admin sudah dibuat. Email dan password acak admin disimpan pada `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env` lokal. Baca file tersebut secara lokal untuk login; jangan memasukkannya ke Git atau membagikan isinya.

Untuk komputer baru, isi kredensial sendiri sebelum seeding. `php artisan db:seed --class=AdminSeeder` aman dijalankan ulang: tidak mereset password, mengaktifkan kembali akun, atau menaikkan akun publik yang kebetulan memakai email sama menjadi admin. Perubahan `.env` tidak mengubah password akun yang sudah ada; gunakan pengaturan kata sandi. Setelah mengubah konfigurasi, jalankan `php artisan config:clear`.

Peran memakai enum `App\Enums\Role` dan kolom enum MySQL, sehingga tidak membutuhkan tabel/seeder peran terpisah. Akun petani/pembeli dapat dibuat dari formulir registrasi.

## Pengujian

`phpunit.xml` memaksa koneksi MySQL dengan nama database **hargatani_testing**, terpisah dari data aplikasi. Suite memakai RefreshDatabase dan dapat menghapus tabel pada database pengujian; jangan mengarahkannya ke database berisi data penting. Host dan kredensial mengikuti konfigurasi DB lokal.

```powershell
php artisan config:clear
php artisan test
php vendor/bin/phpstan analyse --no-progress
php vendor/bin/pint --test
npm.cmd run build
composer validate --no-check-publish
```

Build aset diperlukan sebelum pengujian render halaman. Suite mencakup autentikasi bawaan, pengaturan profil, semua pasangan akses dashboard tiga peran, registrasi admin ilegal, akun nonaktif, kebijakan profil, dan idempotensi seeder.

## Struktur fondasi

- `app/Enums/Role.php`: tiga peran yang didukung.
- `app/Actions/Fortify/CreateNewUser.php`: validasi pendaftaran publik.
- `app/Http/Middleware`: pembatasan peran dan pemutusan sesi akun nonaktif pada permintaan berikutnya.
- `app/Policies/UserPolicy.php`: akses profil sendiri dan batas akses admin.
- `database/migrations`: users, session, cache, jobs, serta peran/status aktif.
- `database/seeders/AdminSeeder.php`: pembuatan admin dari konfigurasi lokal.
- `routes/web.php`: beranda dan dashboard per peran.
- `tests/Feature/FoundationTest.php`: pengujian fondasi akses.

Policy produk dan pesanan akan dibuat bersama modelnya pada tahap berikutnya. Panel moderasi/nonaktifkan akun belum tersedia; fondasi sudah menolak login dan sesi akun dengan `is_active=false`. Konfigurasi Gemini baru berupa placeholder tanpa panggilan API.
