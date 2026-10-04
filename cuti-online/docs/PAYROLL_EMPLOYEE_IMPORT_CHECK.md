# Impor data pegawai dari file payroll

Arahan pengguna: file payroll hanya menjadi sumber pembaruan data pegawai.
Warna, tipografi, dan komponen mengikuti antarmuka sky/slate yang sudah ada.

- PASS: rute unggah membuat pratinjau khusus data pegawai; kolom nominal tidak diwajibkan.
- PASS: nominal gaji/TPP, tunjangan, potongan, transfer, serta rekening tidak masuk payload pratinjau atau audit perubahan.
- PASS: konfirmasi memperbarui pegawai tanpa membuat periode gaji, catatan gaji, atau rekening bank.
- PASS: pratinjau lama tidak dapat dikonfirmasi melalui rute web; pengguna diminta mengunggah ulang.
- PASS: pratinjau menampilkan NIP, nama, pangkat/golongan, jabatan, dan status kepegawaian.
- PASS: mobile 390 × 844 tidak menyebabkan overflow horizontal pada dokumen; tabel memiliki area gulir sendiri.
- PASS: 92 tes aplikasi / 693 assertions; pemeriksaan terakhir 22 tes payroll / 162 assertions; build Vite berhasil.

Data payroll yang sudah tersimpan sebelumnya tidak dihapus.
Migrasi penanda jenis impor sudah dijalankan pada database lokal port 8018.
Bukti visual: `/private/tmp/simpeg-employee-only-preview.png`.
