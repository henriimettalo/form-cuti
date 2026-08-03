# Spesifikasi Formulir Cuti Online

## Tujuan MVP

Aplikasi ini menggantikan pengisian manual formulir Word. Operator atau pegawai
memasukkan data lewat web, sistem menyimpan rekamannya, lalu menghasilkan
formulir `.docx` dan, bila diperlukan, PDF siap cetak.

MVP **tidak** memproses persetujuan elektronik. Nama atasan dan pejabat
berwenang tetap dicetak sebagai blok tanda tangan pada dokumen.

## Peran pengguna

| Peran | Hak akses awal |
| --- | --- |
| Admin | Mengelola seluruh master data, template, akun, dan riwayat dokumen. |
| Operator | Membuat, menyunting, dan mencetak formulir untuk pegawai. |
| Pegawai | Opsional untuk tahap berikutnya; hanya dapat membuat dan melihat formulirnya sendiri. |

## Field form

### 1. Identitas dokumen

| Field | Sumber | Aturan |
| --- | --- | --- |
| Tanggal formulir | Input, default hari ini | Wajib. |
| Nomor internal | Sistem | Dibuat saat formulir pertama kali disimpan. |
| Template | Sistem/admin | Menggunakan template aktif `leave-request`. |

### 2. Data pegawai

Operator memilih pegawai dari master data. Field berikut terisi otomatis,
namun dapat disalin ke snapshot formulir agar dokumen lama tidak berubah bila
master data diperbarui:

| Field | Kolom sumber |
| --- | --- |
| Nama | `employees.full_name` |
| NIP | `employees.nip` |
| Jabatan | `positions.name` atau `employees.position_title` |
| Pangkat/golongan | `employees.rank_name`, `employees.grade` |
| Unit kerja | `departments.name` |
| Masa kerja | Dihitung dari `employees.service_started_on` |

Pada form tambah pegawai, pangkat/golongan PNS dipilih dari dropdown yang
dikelompokkan menurut Golongan I sampai IV. Sistem menyimpan nama pangkat dan
kode golongan ke dua kolom tersebut secara otomatis.

### 3. Data cuti

| Field | Aturan |
| --- | --- |
| Jenis cuti | Wajib; salah satu dari tahunan, besar, sakit, melahirkan, alasan penting, atau di luar tanggungan negara. |
| Alasan cuti | Wajib untuk jenis yang memerlukannya. |
| Tanggal mulai dan selesai | Wajib, tanggal selesai tidak boleh mendahului tanggal mulai. |
| Lama cuti | Dihitung sistem; satuan hari, bulan, atau tahun. Cuti tahunan dihitung sebagai hari kerja. |
| Alamat selama cuti | Opsional, tetapi disarankan diisi. |
| Nomor telepon | Opsional, divalidasi sebagai nomor telepon. |

### 4. Catatan saldo cuti tahunan

Ditampilkan hanya untuk cuti tahunan. Sistem mengambil saldo N-2, N-1, dan
tahun berjalan, menyimpan snapshot sebelum dan sesudah pengajuan, lalu
menggunakannya kembali saat dokumen dicetak ulang.

Validasi awal:

- Pegawai harus aktif.
- Tanggal cuti tidak boleh tumpang tindih dengan formulir aktif pegawai yang sama.
- Lama cuti tahunan tidak boleh melebihi saldo yang tersedia.
- Hari libur pada tabel `holidays` tidak dihitung sebagai hari kerja.

### 5. Blok tanda tangan

Sistem memilih atasan langsung aktif berdasarkan unit kerja pegawai. Pejabat
berwenang disimpan satu kali secara global dan dipakai untuk seluruh unit.
Data nama, jabatan, dan NIP disalin ke snapshot formulir. Pemilihan ini bukan
proses approval.

## Peta placeholder template DOCX

Template Word lama dikonversi sekali ke `.docx`, lalu diberi placeholder:

| Placeholder | Nilai |
| --- | --- |
| `{{form_date}}` | Tanggal formulir dalam format Indonesia. |
| `{{employee_name}}`, `{{employee_nip}}` | Identitas pegawai. |
| `{{position}}`, `{{rank_grade}}`, `{{department}}`, `{{service_period}}` | Data kepegawaian. |
| `{{leave_reason}}`, `{{start_date}}`, `{{end_date}}`, `{{duration}}` | Rincian cuti. |
| `{{address_during_leave}}`, `{{phone_during_leave}}` | Kontak selama cuti. |
| `{{annual_checked}}`, `{{sick_checked}}`, dan seterusnya | Tanda centang jenis cuti. |
| `{{n2_balance}}`, `{{n1_balance}}`, `{{current_balance}}` | Catatan cuti tahunan. |
| `{{supervisor_name}}`, `{{supervisor_nip}}` | Blok atasan langsung. |
| `{{official_name}}`, `{{official_nip}}` | Blok pejabat berwenang. |

## Status formulir

| Status | Makna |
| --- | --- |
| `draft` | Data belum final dan belum menghasilkan dokumen. |
| `generated` | Dokumen Word/PDF sudah dibuat dan diarsipkan. |
| `void` | Formulir dibatalkan; file lama tetap menjadi arsip. |
