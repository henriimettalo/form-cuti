<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Jadwal Publik') · SIMPEG</title>
    <x-favicon />
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    @hasSection('share-url')
        <link rel="canonical" href="@yield('share-url')">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="id_ID">
        <meta property="og:site_name" content="SIMPEG">
        <meta property="og:title" content="@yield('title', 'Jadwal Publik') · SIMPEG">
        <meta property="og:description" content="@yield('description')">
        <meta property="og:url" content="@yield('share-url')">
        <meta property="og:image" content="{{ asset('jadwal-preview.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Jadwal Publik SIMPEG Kecamatan Pontianak Selatan">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    @yield('robots')
    <script>
        if (window.frameElement?.hasAttribute('data-workspace-frame')) {
            document.documentElement.dataset.workspaceFrame = 'true';
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900" data-public-layout>
    @php($publicTab = request()->routeIs('public-schedules.kelurahan') ? 'kelurahan' : request('tab'))
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3" href="#public-content">Langsung ke jadwal</a>
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-10">
            <div>
                <p class="text-lg font-bold tracking-tight text-sky-700">SIMPEG</p>
                <p class="text-xs text-slate-500">Jadwal kegiatan publik</p>
            </div>
            <a class="btn-secondary" href="{{ route('login') }}">Masuk admin</a>
        </div>
    </header>
    <div class="mx-auto max-w-[1440px]">
        <main id="public-content" class="min-w-0 flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            @if ($publicTab)
                <a class="btn-secondary mb-5 print:hidden" href="{{ route('public-schedules.index') }}">← Kembali ke pilihan jadwal</a>
            @endif
            @yield('content')
        </main>
    </div>
    <footer class="mx-auto max-w-[1440px] px-4 pb-6 sm:px-6 lg:px-10 print:hidden">
        <a class="inline-flex min-h-11 items-center text-sm font-medium text-sky-800 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700" href="{{ route('privacy') }}">Pemberitahuan privasi</a>
    </footer>
</body>
</html>
