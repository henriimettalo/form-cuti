<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Jadwal Publik') · SIMPEG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-10">
            <div>
                <p class="text-lg font-bold tracking-tight text-sky-700">SIMPEG</p>
                <p class="text-xs text-slate-500">Jadwal kegiatan publik</p>
            </div>
            <a class="btn-secondary" href="{{ route('login') }}">Masuk admin</a>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-10">
        @yield('content')
    </main>
</body>
</html>
