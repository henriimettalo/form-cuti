@extends('layouts.public')

@section('title', 'Pemberitahuan Privasi')
@section('description', 'Informasi tentang data yang digunakan dalam SIMPEG Kecamatan Pontianak Selatan dan cara menghubungi pengelola.')

@section('content')
    <article class="card mx-auto max-w-3xl">
        <h1 class="page-title">Pemberitahuan privasi</h1>
        <p class="page-description">Kecamatan Pontianak Selatan menggunakan SIMPEG untuk administrasi kepegawaian dan menyediakan jadwal kegiatan untuk umum.</p>

        <div class="mt-6 space-y-6 text-sm leading-relaxed text-slate-700">
            <section>
                <h2 class="section-heading">Data yang digunakan</h2>
                <p class="mt-2">SIMPEG menyimpan data akun, identitas dan riwayat pegawai, formulir dan dokumen cuti, serta informasi payroll dan rekening. Data ini digunakan untuk menjalankan layanan kepegawaian, menyusun dokumen, dan mengatur petugas kegiatan.</p>
            </section>
            <section>
                <h2 class="section-heading">Siapa yang dapat melihat data</h2>
                <p class="mt-2">Akses ke data kepegawaian mengikuti peran dan unit kerja pengguna. Halaman jadwal publik dapat dibuka tanpa akun dan dapat menampilkan unit, kelompok, serta nama petugas yang tercantum pada jadwal. NIP, informasi payroll, dan dokumen cuti tidak ditampilkan pada halaman jadwal publik.</p>
            </section>
            <section>
                <h2 class="section-heading">Sesi dan pencatatan aktivitas</h2>
                <p class="mt-2">SIMPEG menggunakan cookie untuk menjalankan sesi dan menjaga keamanan formulir. Jika Anda memilih “Ingat saya”, sesi masuk dapat dipertahankan pada perangkat tersebut. Aktivitas tertentu, seperti perubahan data pegawai, dicatat untuk penelusuran oleh pengelola.</p>
            </section>
            <section>
                <h2 class="section-heading">Pertanyaan dan koreksi data</h2>
                <p class="mt-2">Untuk menanyakan penggunaan data atau mengajukan koreksi, hubungi Kecamatan Pontianak Selatan di <a class="break-words font-medium text-sky-800 underline underline-offset-4" href="mailto:kec.ptk.selatan@pontianak.go.id">kec.ptk.selatan@pontianak.go.id</a>. Hindari menyertakan kata sandi, nomor rekening, atau dokumen pribadi pada pesan pertama.</p>
            </section>
        </div>
    </article>
@endsection
