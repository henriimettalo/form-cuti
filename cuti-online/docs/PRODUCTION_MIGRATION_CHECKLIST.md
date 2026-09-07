# Checklist Migrasi Produksi

Gunakan checklist ini untuk migrasi metadata unit dan migrasi berikutnya.

## 1. Backup sebelum migrasi

Lakukan backup dari host produksi, di luar direktori publik aplikasi. Jangan pernah memakai `migrate:fresh` pada database produksi.

### SQLite

```bash
mkdir -p storage/backups
cp database/database.sqlite "storage/backups/pre-migration-$(date +%Y%m%d-%H%M%S).sqlite"
ls -lh storage/backups
```

### PostgreSQL

```bash
mkdir -p storage/backups
pg_dump --format=custom --file="storage/backups/pre-migration-$(date +%Y%m%d-%H%M%S).dump" "$DATABASE_URL"
ls -lh storage/backups
```

Pastikan file backup dapat dibaca dan salinannya berada di lokasi penyimpanan terpisah.

## 2. Uji di staging

1. Pulihkan salinan backup produksi yang sudah dianonimkan ke database staging.
2. Gunakan `.env` staging, bukan kredensial produksi.
3. Jalankan:

```bash
php artisan test
php artisan migrate --pretend
php artisan migrate --force
php artisan view:cache
npm run build
```

4. Lakukan smoke test:
   - Super Admin dapat melihat seluruh unit dan akun.
   - Unit nonaktif tidak muncul pada pilihan unit saat membuat Admin Unit.
   - Admin Unit hanya dapat mengelola Pengguna di unitnya.
   - Pengguna dapat membuat formulir cuti, tetapi tidak dapat membuka administrasi akun.
   - Unit Kecamatan dan Kelurahan menampilkan kode serta induk yang benar.
   - Akun pada unit nonaktif tidak dapat login.

## 3. Migrasi produksi

Setelah staging lulus, aktifkan maintenance singkat bila diperlukan, buat backup terakhir, lalu jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache
```

Simpan output migrasi dan nama file backup sebagai catatan deployment.

## 4. Jika harus rollback

Hentikan akses pengguna, simpan log error, lalu ikuti rencana rollback yang sudah disetujui. Untuk migrasi terakhir, rollback kode saja tidak menggantikan pemulihan backup karena metadata unit dapat sudah dipakai oleh data baru.
