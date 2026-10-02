# Pemeriksaan sidebar SIMPEG

Tanggal: 2 Oktober 2026. Lingkup: navigasi aplikasi, bukan redesign halaman modul.

## Arah desain

Mengikuti identitas SIMPEG yang sudah ada: latar terang, teks slate, aksen sky
untuk halaman aktif, ikon sesuai fungsi, dan tipografi aplikasi untuk keterbacaan.
ENERGY 1 / RHYTHM 1 / MOTION 1. Menu vertikal memudahkan pemindaian tujuan;
panel ponsel memberi ruang penuh pada konten. Perubahan keadaan langsung tanpa
animasi perpindahan atau dekorasi tambahan. Breakpoint 48rem menyisakan sekitar
33rem bagi konten sebelum sidebar disembunyikan pada layar sempit.

## Hasil gate antislop dalam lingkup perubahan

- Hard Gate PASS: menu tidak memerlukan geser horizontal; lebar halaman tidak
  melebihi viewport pada ukuran efektif 470px, 878px, dan 1723px. Seluruh 11
  tujuan navigasi membuka halaman/tab. Tombol menu desktop, panel ponsel,
  tombol tutup, klik area luar, dan Escape sudah diuji. Fokus Tab/Shift+Tab
  tetap di panel ponsel; setelah ditutup fokus kembali ke kontrol yang terlihat.
  Target menu minimal 44px, label tombol dan aria-expanded tersedia.
- Purpose Gate PASS: sidebar memakai permukaan putih tanpa blur atau shadow
  tambahan; aksen menunjukkan halaman aktif. Ikon dan tipografi mengikuti
  navigasi SIMPEG yang ada, tanpa menambahkan klaim atau konten fiktif.
- Liveliness PASS: aksen halaman aktif, pengelompokan menu, dan ruang antar
  kelompok memberi hierarki pada navigasi sesuai dials di atas.
- Craftsmanship PASS: tab workspace tetap berfungsi; iframe tidak menampilkan
  sidebar atau tombol menu kedua. Tidak ada error konsol saat seluruh tujuan
  menu dibuka. Build Vite dan 84 test Laravel (632 assertion) lulus.

Ukuran browser efektif berbeda dari override karena zoom browser pengguna.
Pengujian visual dilakukan di aplikasi lokal; hosting belum diperbarui karena
akses deployment belum tersedia.
