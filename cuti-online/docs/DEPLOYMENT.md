# Deployment Docker di VPS

Konfigurasi ini menjalankan aplikasi pada `https://cuti.kecamatanpontianakselatan.web.id` dengan layanan berikut:

- Nginx yang sudah terpasang di VPS untuk HTTPS dan reverse proxy;
- Nginx internal Docker untuk web server dan PHP-FPM;
- Laravel PHP-FPM, worker antrean, serta scheduler;
- PostgreSQL 17;
- volume persisten untuk database, dokumen Word, dan unggahan.

Database dan Nginx Docker tidak dibuka ke internet. Nginx Docker hanya mendengar pada `127.0.0.1:8088`; Nginx VPS meneruskan trafik HTTPS kepadanya.

## Prasyarat VPS

- Ubuntu/Debian VPS dengan minimal 2 GB RAM dan ruang disk 20 GB;
- Docker Engine dan Docker Compose plugin;
- port `80` dan `443` terbuka;
- record DNS Cloudflare `A` bernama `cuti` mengarah ke alamat IPv4 VPS.

Di Cloudflare, gunakan SSL/TLS mode **Full (strict)**. Jangan gunakan mode Flexible karena akan menyebabkan redirect loop. Pastikan Nginx VPS yang sudah aktif tetap menjadi satu-satunya layanan yang memakai port `80` dan `443`.

## Konfigurasi Nginx VPS

Salin [docker/nginx/host-cuti.conf](../docker/nginx/host-cuti.conf) ke VPS, lalu aktifkan sebagai virtual host Nginx:

```bash
sudo cp docker/nginx/host-cuti.conf /etc/nginx/sites-available/cuti.kecamatanpontianakselatan.web.id
sudo ln -s /etc/nginx/sites-available/cuti.kecamatanpontianakselatan.web.id /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Konfigurasi tersebut meneruskan domain ke `127.0.0.1:8088`. Jika port tersebut sudah dipakai, pilih port lokal lain yang kosong, lalu ubah nilai `APP_HTTP_PORT` di `.env.production` **dan** `proxy_pass` di berkas Nginx VPS menjadi nilai yang sama.

Setelah DNS mengarah ke VPS, terbitkan sertifikat HTTPS melalui Certbot:

```bash
sudo certbot --nginx -d cuti.kecamatanpontianakselatan.web.id
```

Jika `certbot` belum tersedia, instal terlebih dahulu sesuai dokumentasi sistem operasi VPS. Setelah sertifikat aktif, gunakan mode Cloudflare **Full (strict)**.

## Deploy pertama

Jalankan dari direktori aplikasi di VPS.

```bash
cp .env.production.example .env.production
```

Edit `.env.production` dan isi `POSTGRES_PASSWORD` serta `SIMPEG_DB_PASSWORD` dengan dua kata sandi acak yang berbeda. Kata sandi runtime minimal 32 karakter. Nilai `APP_HTTP_PORT=8088` hanya dapat diakses dari VPS sendiri, bukan dari internet. Jangan mengunggah berkas ini ke Git atau membagikannya.

Buat `APP_KEY` baru:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml build app
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml run --rm --no-deps app php artisan key:generate --show
```

Salin nilai yang dihasilkan, termasuk awalan `base64:`, ke `APP_KEY` pada `.env.production`. Setelah itu jalankan:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml up -d --build
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml ps
```

Layanan `init` menjalankan migrasi, menyiapkan akun PostgreSQL runtime dengan hak baca dan tulis data aplikasi, lalu menyalin aset hasil build. Layanan app, worker, dan scheduler memakai akun runtime. Layanan ClamAV berjalan di jaringan Docker. Unggahan ditolak jika pemeriksaan malware tidak tersedia. Jangan menjalankan `migrate:fresh` pada VPS karena perintah tersebut menghapus data.

Saat memperbarui instalasi lama, tambahkan `SIMPEG_DB_USER=cuti_online_app` dan `SIMPEG_DB_PASSWORD` ke `.env.production` sebelum menjalankan Compose. Pengguna perlu masuk kembali saat enkripsi sesi pertama kali diaktifkan. Token API yang sebelumnya tanpa batas memperoleh waktu 90 hari untuk rotasi.

Buat akun administrator pertama:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml exec app php artisan app:create-admin "Nama Admin" admin@kecamatanpontianakselatan.web.id
```

Masukkan kata sandi minimal 12 karakter melalui prompt terminal. Di produksi, MFA wajib secara bawaan. Admin yang belum mengaktifkannya diarahkan ke pendaftaran perangkat setelah login, melalui halaman **Verifikasi dua langkah**. Untuk instalasi lama yang masih menetapkan `REQUIRE_ADMIN_MFA=false`, ubah ke `true` setelah admin mendaftarkan perangkat dan memastikan bisa login kembali, lalu buat ulang container app.

Untuk pemulihan akun, verifikasi identitas pemilik akun melalui prosedur instansi. Jalankan `php artisan app:issue-password-recovery` melalui terminal container app dan berikan kode 30 menit secara privat. Pengguna memasukkannya melalui tautan **Pulihkan kata sandi** di halaman login. Jangan menyalin kode ke tiket dukungan atau log. Jika perangkat autentikator hilang, gunakan `php artisan app:clear-admin-mfa` setelah verifikasi untuk mencabut sesi serta token dan mengizinkan pendaftaran perangkat baru.

## Verifikasi

```bash
curl -I https://cuti.kecamatanpontianakselatan.web.id/up
curl -i https://cuti.kecamatanpontianakselatan.web.id/api/v1/employees
```

Endpoint kesehatan harus merespons `200`. Endpoint API tanpa Bearer Token harus merespons `401 Unauthorized`; ini menandakan API terlindungi dengan benar.

Token integrasi dibuat melalui menu **Integrasi API** setelah login. Base URL API adalah:

```text
https://cuti.kecamatanpontianakselatan.web.id/api/v1
```

API membatasi 120 permintaan per menit per IP sebelum autentikasi. Akun terautentikasi juga dibatasi 60 permintaan per menit. Nginx tepi perlu meneruskan IP klien yang sudah diverifikasi ke aplikasi melalui proxy tepercaya agar beberapa klien tidak dianggap berasal dari IP proxy yang sama.

Akses API lintas origin dinonaktifkan secara bawaan. Isi `API_CORS_ORIGINS` hanya jika browser perlu memanggil API dari origin lain. Gunakan daftar origin lengkap (skema dan host, termasuk port jika diperlukan), dipisah koma. Integrasi server-ke-server tetap memakai Bearer Token.

Gunakan template DOCX dengan gambar tertanam. Referensi eksternal pada bagian relasi DOCX ditolak saat unggah dan saat template dipakai untuk membuat dokumen.

## Memantau dan memperbarui

Lihat log layanan:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml logs -f web app worker
```

Log Laravel produksi menyimpan tingkat log dan lokasi pengecualian tanpa pesan mentah, konteks, atau trace. Log akses Nginx container tidak mencatat IP, URL, query, atau header. Docker menyimpan maksimal tiga berkas log dengan batas 10 MB per berkas per layanan. Batasi akses ke Docker di VPS dan terapkan kebijakan yang sama pada log reverse proxy di host, karena konfigurasinya dikelola terpisah.

Setelah pembaruan kode:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml up -d --build --force-recreate
```

Perintah ini menjalankan ulang layanan `init`, sehingga migrasi baru dan aset frontend diterapkan.

## Cadangan data

Cadangkan dua hal secara berkala:

1. database PostgreSQL;
2. volume `app_storage`, karena berisi template dan dokumen cuti yang dibuat.

Contoh cadangan database dari VPS:

```bash
mkdir -p backups
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml exec -T postgres pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" > backups/cuti-online-$(date +%F).sql
```

Simpan cadangan di lokasi terpisah dari VPS. Jangan hanya mengandalkan volume Docker pada server yang sama.

## Jika sertifikat HTTPS belum terbit

Pastikan DNS sudah mengarah ke VPS, port `80`/`443` terbuka, dan virtual host Nginx untuk domain tersebut sudah aktif. Jika validasi Certbot melalui Cloudflare terhalang, ubah record `cuti` menjadi **DNS only** sementara, jalankan ulang perintah `certbot`, lalu aktifkan kembali proxy Cloudflare setelah HTTPS aktif.
