# Judul Pegawai saat menggulir

Perubahan mengikuti warna sky/slate dan tipografi aplikasi yang sudah ada.
Judul ringkas setinggi 48 px muncul setelah judul asli melewati batas atas
halaman atau area tab. Deskripsi dan tombol tetap berada pada posisi semula.

- PASS: halaman Pegawai langsung, desktop 1128 × 687; judul tetap terlihat saat menggulir dan hilang saat kembali ke atas.
- PASS: tab Pegawai dalam workspace; menggulir iframe menampilkan judul di atas area tab.
- PASS: beralih ke Dashboard menyembunyikan judul Pegawai; kembali ke tab Pegawai memulihkannya.
- PASS: ponsel 390 × 844; tinggi judul 48 px, tanpa overflow horizontal pada dokumen utama.
- PASS: judul salinan bersifat dekoratif (`aria-hidden`); heading asli tetap tersedia untuk pembaca layar.
- PASS: build Vite selesai.

Bukti visual: `/private/tmp/simpeg-sticky-title-desktop.png`,
`/private/tmp/simpeg-sticky-title-workspace.png`, dan
`/private/tmp/simpeg-sticky-title-mobile.png`.
