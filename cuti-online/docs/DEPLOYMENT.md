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

Edit `.env.production`, lalu ganti minimal `POSTGRES_PASSWORD` dengan password acak yang panjang. Nilai `APP_HTTP_PORT=8088` hanya dapat diakses dari VPS sendiri, bukan dari internet. Jangan mengunggah berkas ini ke Git atau membagikannya.

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

Layanan `init` akan menjalankan migrasi database dan menyalin aset hasil build. Jangan menjalankan `migrate:fresh` pada VPS karena perintah tersebut menghapus data.

Buat akun administrator pertama:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml exec app php artisan app:create-admin "Nama Admin" admin@kecamatanpontianakselatan.web.id
```

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

## Memantau dan memperbarui

Lihat log layanan:

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml logs -f web app worker
```

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
