# Dialog konfirmasi aplikasi

Menggunakan warna, tombol, tipografi, dan dialog putih yang sudah digunakan
pada konfirmasi impor pegawai.

- PASS: seluruh enam pemanggilan `confirm()` browser pada views diganti dengan konfirmasi aplikasi.
- PASS: tombol Batalkan membuka dialog dengan judul dan konsekuensi tindakan yang jelas.
- PASS: fokus awal pada Kembali; Escape menutup dialog dan mengembalikan fokus ke pemicu.
- PASS: Kembali menutup dialog tanpa mengirim formulir atau membatalkan pratinjau.
- PASS: mobile 390 × 844; dialog selebar 358 px berada dalam viewport.
- PASS: build Vite dan `git diff --check`.

Bukti desktop: `/private/tmp/simpeg-styled-confirmation.png`.
Konfirmasi akhir yang mengubah data pengguna tidak dijalankan untuk pemeriksaan visual.
