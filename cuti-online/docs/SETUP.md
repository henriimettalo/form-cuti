# Menjalankan SIMPEG

## Pengembangan lokal

Proyek baru menggunakan SQLite agar dapat langsung dijalankan. Untuk membuka
aplikasi:

```bash
composer install
npm install
npm run build
php artisan migrate --seed
php artisan app:create-admin "Nama Admin" admin@example.go.id
php artisan serve
```

Buka alamat yang ditampilkan Laravel, lalu masuk menggunakan akun admin yang
baru dibuat.

## PostgreSQL untuk server

Salin `.env.postgres.example` menjadi `.env`, isi `APP_KEY` dan seluruh
password dengan nilai produksi yang kuat. Pastikan ekstensi PHP
`pdo_pgsql` tersedia pada server.

Database PostgreSQL dapat dijalankan dengan Docker Compose:

```bash
export POSTGRES_PASSWORD="ganti-dengan-password-kuat"
docker compose up -d postgres
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan app:create-admin "Nama Admin" admin@example.go.id
```

Jalankan `npm run build` setiap ada perubahan tampilan. Simpan backup
PostgreSQL dan folder `storage/app/private/leave-documents` secara berkala,
karena folder tersebut menyimpan arsip file Word.

## Catatan produksi

- Semua halaman operator memerlukan login.
- Aplikasi saat ini menghasilkan DOCX. PDF dapat ditambahkan pada server yang
  memasang LibreOffice headless.
- Jangan menyimpan `.env`, file backup database, atau dokumen cuti pada
  repositori publik.
