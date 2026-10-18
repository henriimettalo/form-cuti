# Catatan Migrasi VPS — Cuti Online

Dokumentasi perjalanan migrasi aplikasi dari VPS lama (43.134.32.220, Tencent Cloud)
ke VPS baru (168.110.221.52, Oracle Cloud / OCI) pada 17 Oktober 2026.
Meliputi seluruh langkah, masalah yang ditemui, dan solusinya.

## Ringkasan

| Item | VPS Lama | VPS Baru |
| --- | --- | --- |
| Provider | Tencent Cloud (VM-7-7-ubuntu) | Oracle Cloud (vnic-5) |
| IP Publik | 43.134.32.220 | 168.110.221.52 |
| OS | Ubuntu | Ubuntu 24.04 (kernel oracle) |
| Arsitektur | x86_64 | arm64 (aarch64) |
| Status akhir | Dimatikan (rollback cadangan) | Produksi aktif |

Stack aplikasi tidak berubah: Docker Compose (`compose.yaml` +
`compose.production.yaml`), PHP-FPM + Nginx internal, PostgreSQL 17, worker,
scheduler, dan Nginx host sebagai reverse proxy HTTPS.

## Tahap 1 — Persiapan VPS Baru

### 1.1 Verifikasi Docker

```bash
docker compose version
# Docker Compose version v5.6.0
```

Docker Engine dan Compose plugin sudah terpasang (image bawaan OCI).

### 1.2 Masalah: pending kernel upgrade

Setelah instalasi paket, muncul peringatan:

```text
Pending kernel upgrade!
Running kernel version: 6.17.0-1020-oracle
Expected: 7.0.0-1013-oracle
```

**Solusi:** reboot sebelum melanjutkan (`sudo reboot`), karena restart layanan
ditangguhkan (termasuk `docker.service`).

### 1.3 Masalah: `ufw: command not found`

Image Ubuntu OCI tidak menyertakan ufw secara default, dan justru memakai
`iptables-persistent`.

**Solusi:**

```bash
sudo apt update
sudo apt install -y ufw   # otomatis menghapus iptables-persistent
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable           # jawab "y" pada peringatan SSH
```

Setelah reboot, verifikasi: `sudo ufw status` — OpenSSH, 80/tcp, 443/tcp ALLOW
(v4 dan v6).

### 1.4 Masalah: `permission denied` pada Docker socket

```text
permission denied while trying to connect to the docker API at unix:///var/run/docker.sock
```

Penyebab: user `ubuntu` belum masuk grup `docker`, dan perubahan grup hanya
berlaku untuk sesi login baru.

**Solusi:**

```bash
sudo usermod -aG docker $USER
newgrp docker          # aktifkan grup tanpa logout
docker run --rm hello-world
```

Catatan: jangan lanjutkan sebagai root (`sudo su`); pemakaian root membuat file
hasil clone dimiliki root dan menyulitkan deploy script nanti.

## Tahap 2 — Clone Repository

Repo privat di GitHub (`henriimettalo/form-cuti`), jadi dipakai deploy key:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/cuti-deploy -N "" -C "cuti-vps"
cat ~/.ssh/cuti-deploy.pub
```

Baris `ssh-ed25519 AAAA... cuti-vps` ditambahkan ke
**GitHub → repo → Settings → Deploy keys** (read-only cukup).

### 2.1 Masalah: bingung bagian mana "kunci"nya

Output `ssh-keygen` berisi fingerprint (`SHA256:...`), gambar randomart, dan
public key. Yang ditempel ke GitHub hanya **satu baris public key**
(`ssh-ed25519 ... cuti-vps`). Fingerprint tidak bisa dipakai sebagai kunci.
Randomart hanyalah visualisasi fingerprint, tidak perlu disimpan.

### 2.2 Uji koneksi dan clone

```bash
ssh -i ~/.ssh/cuti-deploy -T git@github.com
# "Hi henriimettalo/form-cuti! You've successfully authenticated..."

sudo mkdir -p /opt/form-cuti
sudo chown ubuntu:ubuntu /opt/form-cuti
GIT_SSH_COMMAND="ssh -i ~/.ssh/cuti-deploy" \
  git clone git@github.com:henriimettalo/form-cuti.git /opt/form-cuti/cuti-online

cd /opt/form-cuti/cuti-online
git config core.sshCommand "ssh -i ~/.ssh/cuti-deploy"   # agar `git pull` polos tetap jalan
```

### 2.3 Masalah: struktur repo nested

Aplikasi Laravel berada di subdirektori `cuti-online/` di dalam repo, sehingga
path aplikasi di VPS menjadi:

```text
/opt/form-cuti/cuti-online/cuti-online/
```

**Solusi:** semua perintah `docker compose` dan deploy script dijalankan dari
direktori dalam tersebut (bukan dari root clone).

## Tahap 3 — Ekspor Data dari VPS Lama

Data produksi tidak ada di Git; semuanya di Docker volume dan database.

```bash
cd /opt/form-cuti/cuti-online
set -a; . ./.env.production; set +a

# Dump database
docker compose -f compose.yaml -f compose.production.yaml exec -T postgres \
  pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" > /root/cuti-db.sql

# Arsip volume storage (template + dokumen cuti)
docker volume ls | grep app_storage   # nama: cuti-online_app_storage
docker run --rm -v cuti-online_app_storage:/vol -v /root:/out alpine \
  tar czf /out/cuti-storage.tar.gz -C /vol .
```

### 3.1 Masalah: placeholder ditempel mentah ke shell

Perintah pertama gagal karena placeholder `<exact_volume_name>` dan backslash
markdown (`\~`) ikut terpaste:

```text
-bash: exact_volume_name: No such file or directory
```

**Solusi:** ganti placeholder dengan nilai asli (`cuti-online_app_storage`) dan
hilangkan backslash. Pelajaran: selalu jalankan `docker volume ls` dulu.

### 3.2 Hasil ekspor

```text
/root/cuti-db.sql          184K   -- PostgreSQL database dump
/root/cuti-storage.tar.gz  111K
```

## Tahap 4 — Transfer File antar VPS

### 4.1 Masalah: tanda `<...>` dan `\~` ikut terpaste

```text
bash: 43.134.32.220: No such file or directory
```

Kurung sudut `< >` hanyalah penanda placeholder — harus dihapus saat diisi IP asli.

### 4.2 Masalah: `Permission denied (publickey)` ke root@ VPS lama

SSH root di VPS lama hanya menerima kunci yang sudah terdaftar; VPS baru belum
punya kunci di sana.

**Solusi:** salin file ke home user `ubuntu` di VPS lama (yang kuncinya sudah
ada), lalu `scp` sebagai user tersebut:

```bash
# di VPS lama (sebagai root)
cp /root/cuti-db.sql /root/cuti-storage.tar.gz /home/ubuntu/
chown ubuntu:ubuntu /home/ubuntu/cuti-*

# di VPS baru
scp ubuntu@43.134.32.220:/home/ubuntu/cuti-db.sql \
    ubuntu@43.134.32.220:/home/ubuntu/cuti-storage.tar.gz ~/
# cuti-db.sql         100%  184KB
# cuti-storage.tar.gz 100%  110KB
```

## Tahap 5 — Restore di VPS Baru

### 5.1 Siapkan `.env.production`

```bash
cd /opt/form-cuti/cuti-online/cuti-online
cp .env.production.example .env.production
```

Nilai penting:

- `APP_KEY` — disalin **persis** dari VPS lama (jika diganti, seluruh sesi dan
  data terenkripsi tidak valid);
- `POSTGRES_PASSWORD` — password acak panjang;
- `SESSION_ENCRYPT=true` — ditambahkan (bagian dari perbaikan keamanan);
- `TRUSTED_PROXIES` — opsional, default sudah mencakup subnet Docker.

### 5.2 Restore database

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml up -d postgres
# tunggu status "healthy"

set -a; . ./.env.production; set +a
cat ~/cuti-db.sql | docker compose --env-file .env.production \
  -f compose.yaml -f compose.production.yaml \
  exec -T postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"
```

Verifikasi:

```bash
... exec -T postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" \
  -c "SELECT count(*) FROM employees;" -c "SELECT count(*) FROM users;"
# employees: 63, users: 1
```

### 5.3 Masalah: volume `app_storage` belum ada

```bash
docker volume ls | grep app_storage   # kosong
```

Volume baru dibuat saat service `app` pertama kali jalan. **Solusi:** jalankan
stack penuh dulu, baru ekstrak arsip ke dalam volume (urutan ini juga benar
karena `init` menyalin aset build dulu, lalu arsip menimpanya dengan data asli):

```bash
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml up -d --build
# semua container healthy: postgres, app, web, worker, scheduler

docker run --rm -v cuti-online_app_storage:/vol -v ~:/in alpine \
  tar xzf /in/cuti-storage.tar.gz -C /vol

docker run --rm -v cuti-online_app_storage:/vol alpine ls /vol/app
# private  public
```

### 5.4 Uji lokal

```bash
curl -I http://127.0.0.1:8088/up
# HTTP/1.1 200 OK  (nginx/1.28.3 — nginx internal Docker)
```

## Tahap 6 — Nginx Host, DNS, dan HTTPS

### 6.1 Pasang vhost Nginx host

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp docker/nginx/host-cuti.conf /etc/nginx/sites-available/cuti.kecamatanpontianakselatan.web.id
sudo ln -s /etc/nginx/sites-available/cuti.kecamatanpontianakselatan.web.id /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

curl -I http://127.0.0.1/up -H "Host: cuti.kecamatanpontianakselatan.web.id"
# HTTP/1.1 200 OK (nginx/1.24.0 Ubuntu — nginx host)
```

### 6.2 Masalah: domain masih menunjuk IP Cloudflare

```bash
dig +short cuti.kecamatanpontianakselatan.web.id
# 104.21.0.159, 172.67.151.68  ← IP edge Cloudflare, bukan VPS
```

**Solusi** di dashboard Cloudflare:

1. Edit record A `cuti`: Content `43.134.32.220` → `168.110.221.52`
   (record lain seperti `aula`, `skrt` tidak diubah — masih ke VPS lama);
2. Proxy status: **DNS only** (awan abu-abu) sementara, supaya certbot bisa
   verifikasi langsung ke origin.

Verifikasi: `dig +short ...` menampilkan `168.110.221.52`.

### 6.3 Masalah: certbot timeout — "likely firewall problem"

```text
Detail: 168.110.221.52: Fetching http://.../.well-known/acme-challenge/...:
Timeout during connect (likely firewall problem)
```

Padahal ufw sudah ALLOW 80/443 dan nginx listen. Diagnosis: `curl` ke IP publik
bahkan dari VPS sendiri ikut timeout — artinya diblokir **di luar VPS**, yaitu
**OCI Security List** (firewall jaringan Oracle Cloud di console web).

**Solusi** di OCI Console (cloud.oracle.com):

**Networking → Virtual Cloud Networks → VCN → subnet → Default Security List →
Add Ingress Rules**, dua aturan:

| Source CIDR | Protocol | Dest. Port |
| --- | --- | --- |
| 0.0.0.0/0 | TCP | 80 |
| 0.0.0.0/0 | TCP | 443 |

(Sebelumnya hanya ada SSH port 22 + aturan ICMP.)

Catatan: kalau instance memakai **Network Security Group (NSG)**, aturan harus
ditambahkan di NSG tersebut — NSG menggantikan Security List.

Setelah ditambah:

```bash
curl -I --max-time 10 http://168.110.221.52/up -H "Host: cuti.kecamatanpontianakselatan.web.id"
# HTTP/1.1 200 OK  ← port 80 terbuka dari internet
```

### 6.4 Terbitkan sertifikat

```bash
sudo certbot --nginx -d cuti.kecamatanpontianakselatan.web.id
# Successfully received certificate.
# Certificate expires on 2027-01-15. Auto-renewal aktif.
```

Pertanyaan certbot:

- **Email** — email admin yang benar-benar dibaca (notifikasi kedaluwarsa);
- **Terms of Service** — Yes;
- **Share email ke EFF** — opsional (dipilih No).

### 6.5 Verifikasi akhir

```bash
curl -I https://cuti.kecamatanpontianakselatan.web.id/up
# HTTP/2 200  (server: cloudflare — proxy sudah diaktifkan lagi)

curl -i https://cuti.kecamatanpontianakselatan.web.id/api/v1/employees
# HTTP/2 401  {"message":"Unauthenticated."}  ← API terproteksi
```

Kemudian di Cloudflare: proxy `cuti` dikembalikan ke **Proxied** (awan oranye)
dan SSL/TLS mode diset **Full (strict)**.

## Tahap 7 — Penutupan VPS Lama

```bash
# di VPS lama
docker compose -f compose.yaml -f compose.production.yaml down
```

Semua container dan network dihapus; **volume data sengaja dibiarkan** sebagai
cadangan rollback ±1 minggu. Setelah yakin stabil, barulah volume/instance lama
boleh dibersihkan (`down -v` atau terminate instance).

## Daftar Masalah dan Solusi (Ringkas)

| # | Masalah | Penyebab | Solusi |
| --- | --- | --- | --- |
| 1 | Pending kernel upgrade | Image OCI baru diupdate | `sudo reboot` sebelum lanjut |
| 2 | `ufw: command not found` | OCI tidak menyertakan ufw | `apt install ufw` (menggantikan iptables-persistent) |
| 3 | Docker `permission denied` | User belum di grup docker | `usermod -aG docker` + `newgrp docker` |
| 4 | Bingung fingerprint vs public key | Output ssh-keygen banyak bagian | Yang ditempel hanya baris `ssh-ed25519 ...` |
| 5 | Struktur repo nested | App ada di subfolder `cuti-online/` | Semua perintah dijalankan dari direktori dalam |
| 6 | Placeholder `<...>`/`\~` terpaste | Copy dari markdown mentah | Ganti nilai asli, hilangkan backslash |
| 7 | `Permission denied (publickey)` root@ VPS lama | Kunci VPS baru belum terdaftar | Pindahkan file ke home user biasa, scp sebagai user itu |
| 8 | Volume app_storage belum ada | Volume dibuat saat app start | `up -d --build` dulu, baru ekstrak arsip |
| 9 | DNS menunjuk IP Cloudflare | Record diproksi + IP lama | Update A record, set DNS only sementara |
| 10 | Certbot timeout | OCI Security List hanya buka port 22 | Tambah Ingress Rules TCP 80 & 443 |

## Checklist Operasional Setelah Migrasi

- [ ] Uji login di browser dan buat/unduh formulir cuti (end-to-end);
- [ ] Cloudflare SSL/TLS mode = **Full (strict)**;
- [ ] Update deploy script: path kerja `/opt/form-cuti/cuti-online/cuti-online`;
- [ ] Jadwalkan backup rutin (`pg_dump` + tar volume `app_storage`) — contoh di
      [DEPLOYMENT.md](DEPLOYMENT.md);
- [ ] Setelah ±1 minggu stabil, bersihkan VPS lama;
- [ ] Sertifikat auto-renew certbot sudah aktif — cek berkala dengan
      `sudo certbot renew --dry-run`.

## Perintah Berguna

```bash
# Masuk direktori aplikasi
cd /opt/form-cuti/cuti-online/cuti-online

# Deploy/update (setelah script disesuaikan path-nya)
git pull --ff-only origin main
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml up -d --build

# Log dan status
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml ps
docker compose --env-file .env.production -f compose.yaml -f compose.production.yaml logs -f web app worker

# Backup manual database
set -a; . ./.env.production; set +a
docker compose -f compose.yaml -f compose.production.yaml exec -T postgres \
  pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" > ~/cuti-db-$(date +%F).sql
```
