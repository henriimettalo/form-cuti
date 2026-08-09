# SIMPEG Cuti Online — Sistem Kepegawaian & Cuti Kecamatan

Aplikasi web pengelolaan data pegawai, pengajuan cuti, dan payroll untuk lingkungan pemerintahan kecamatan. Dibangun dengan Laravel + SQLite.

## Fitur

- **Master Pegawai** — kelola identitas pegawai (NIP, nama, jabatan, kontak), import identitas dari Excel (NIP+nama saja), arsip/pulihkan, bulk archive.
- **Cuti** — buat formulir cuti tahunan, hitung durasi otomatis, snapshot data pegawai + pejabat penandatangan, generate dokumen Word (.docx) dari template.
- **Pejabat berwenang** — kelola pejabat penandatangan: Pejabat Berwenang, Sekda, Wali Kota. Khusus pemohon Camat, atasan & pejabat berwenang otomatis diisi; dukungan PLH.
- **Payroll** — import gaji utama (Gaji PNS) + pelengkap TPP dari Excel, per periode bulanan. Update data master pegawai dari file, rekonsiliasi total, ekspor/slip.
- **Template dokumen** — unggah template Word formulir cuti.
- **Profil instansi** — nama instansi dipakai pada formulir.

## Persyaratan

- PHP 8.2+
- Composer
- Node.js 18+ (untuk build aset Vite)
- SQLite (default) atau PostgreSQL

## Instalasi

```bash
# 1. Clone / salin folder project

# 2. Install dependensi
composer install
npm install

# 3. Siapkan environment
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi .env (sesuaikan nama instansi, DB, dsb.)
#    APP_NAME=NamaInstansi

# 5. Migrasi + seed data awal (jenis cuti)
php artisan migrate
php artisan db:seed

# 6. Build aset frontend
npm run build
# atau saat pengembangan: npm run dev

# 7. Jalankan server
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Membuat akun admin

Tidak ada seeder akun. Buat pengguna lewat `php artisan tinker`:

```php
php artisan tinker

App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.go.id',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);
```

Role yang diakui untuk akses data sensitif & bulk delete: `admin`, `administrator`, `pejabat`, `simpeg_admin`.

## Alur kerja umum

1. **Isi Profil Instansi** (`/profil-instansi`) — nama instansi.
2. **Tambah pegawai** — input NIP + nama + jabatan (+ kontak). Detail lain dikosongkan.
3. **Lengkapi data pegawai** — lewat menu **Payroll → Impor** (unggah file Gaji PNS; data master ikut terisi). File TPP sebagai pelengkap.
4. **Buat formulir cuti** (`/formulir-cuti`) — pilih pegawai, tanggal, durasi; atasan & pejabat berwenang otomatis. Cetak dokumen Word.
5. **Import payroll per bulan** — `Payroll → Impor`, pilih bulan, jenis file (Gaji utama / TPP), periksa pratinjau, konfirmasi.

## Menjalankan test

```bash
php artisan test
```

## Struktur penting

| Path | Isi |
|---|---|
| `app/Models/` | Eloquent models (Employee, LeaveRequest, PayrollPeriod, dst) |
| `app/Http/Controllers/` | Controller per modul |
| `app/Services/` | Logika bisnis: import, generator dokumen, durasi cuti |
| `resources/views/` | Blade views |
| `database/migrations/` | Skema DB |
| `routes/web.php` | Route aplikasi |

## Replikasi ke kecamatan lain

Aplikasi dirancang untuk struktur **kecamatan dalam pemerintahan kota dengan Wali Kota** (alur: Camat → Sekda → Wali Kota). Untuk replikasi:

1. Salin folder, jalankan instalasi di atas.
2. Isi `.env` dengan nama instansi & kredensial baru.
3. Migrasi + seed.
4. Isi **Profil Instansi**.
5. Unggah template dokumen Word sesuai instansi.
6. Import data pegawai (menu **Impor identitas**) lalu payroll.

> Catatan: alamat tujuan pada dokumen (`Yth. Wali Kota ...`) masih perlu disesuaikan per instansi di `app/Services/LeaveDocumentGenerator.php` atau template yang diunggah.

## Lisensi

Proyek internal pemerintahan.
