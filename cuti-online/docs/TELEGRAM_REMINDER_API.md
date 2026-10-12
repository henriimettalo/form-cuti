# Reminder piket kecamatan

## Endpoint reminder lain

Semua endpoint menerima parameter opsional `date=YYYY-MM-DD`, default hari ini
di Asia/Pontianak, dan mengembalikan kontrak `date`, `timezone`, `has_schedule`,
`isAvailable` (integer 0/1), `data`, dan `message` yang sama.

| Jadwal | Endpoint | Ability token |
| --- | --- | --- |
| Apel / Upacara | `/api/v1/ceremony-schedules/reminder` | `ceremony-schedules:read` |
| Piket Kecamatan | `/api/v1/counter-duty-schedules/reminder` | `counter-duty-schedules:read` |
| Piket Kelurahan | `/api/v1/kelurahan-duty-schedules/reminder` | `counter-duty-schedules:read` |

Apel/upacara menyediakan kelompok, unit petugas, pemimpin, waktu, lokasi,
dan pesan. Data ini tidak memiliki daftar anggota; field yang belum diisi
ditampilkan sebagai "Belum ditentukan" dalam pesan. Libur bersama dikecualikan.

Piket kelurahan menyediakan satu item per jadwal kelurahan aktif pada tanggal
terpilih: kelompok, identitas kelurahan, penanggung jawab, anggota, dan pesan.
Template kelompok tanpa tanggal tidak disertakan. Pesan tingkat atas menggabungkan
seluruh pesan kelurahan; pesan tiap item dapat dipakai untuk mengirim terpisah.

## Pemakaian piket kecamatan

`GET /api/v1/counter-duty-schedules/reminder?date=2026-10-12`

Header wajib: `Authorization: Bearer TOKEN_API` dan `Accept: application/json`.
Token milik super admin harus memiliki ability `counter-duty-schedules:read`.

Parameter `date` opsional, format `YYYY-MM-DD`. Jika tidak diisi, endpoint
menggunakan tanggal hari ini dalam zona waktu Asia/Pontianak.

Respons JSON memuat `date`, `timezone`, `has_schedule`, `isAvailable`, `data`, dan `message`.
Setiap item `data` memuat `id`, `group_number`, `coordinator`, `members`, dan
`message`. Field `message` tingkat atas adalah teks siap kirim ke Telegram;
jika ada beberapa jadwal, pesan digabung dengan baris kosong. Kirim sebagai
teks biasa tanpa `parse_mode` agar tanda baca nama tidak ditafsirkan sebagai markup.

`isAvailable` adalah integer: `1` jika ada jadwal, `0` jika tidak ada.
Backend pemanggil dapat mengakhiri eksekusi cron saat `isAvailable === 0`;
endpoint tidak menonaktifkan penjadwalan cron berikutnya.
Bot sebaiknya hanya mengirim reminder jika `isAvailable === 1`.
Jika tidak ada jadwal, respons tetap `200`, `data` kosong, dan `message`
menjelaskan bahwa tidak ada jadwal pada tanggal tersebut. Libur bersama dan
jadwal kelurahan tidak disertakan. Snapshot susunan petugas pada jadwal
diutamakan; jika snapshot tidak ada, digunakan kelompok terkait.

Tanggal tidak valid menghasilkan `422`; tanpa token `401`; tanpa izin `403`.
Endpoint hanya menyediakan pesan, tidak mengirim ke Telegram secara langsung.

```sh
curl -H "Authorization: Bearer $TOKEN_API" \
  -H "Accept: application/json" \
  'http://127.0.0.1:8000/api/v1/counter-duty-schedules/reminder?date=2026-10-12'
```
