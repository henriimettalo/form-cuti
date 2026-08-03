<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Masuk · SIMPEG</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950">
        <main class="grid min-h-screen place-items-center px-4 py-10">
            <div class="w-full max-w-md">
                <div class="mb-7 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-sky-500 text-sm font-bold tracking-tight text-white shadow-[0_10px_28px_rgba(14,165,233,0.25)]">SK</div>
                    <p class="mt-5 text-sm font-semibold uppercase tracking-[0.16em] text-sky-300">SIMPEG</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-[-0.03em] text-white [text-wrap:balance]">Masuk sebagai operator.</h1>
                    <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-400">Akses data, riwayat kepegawaian, dan arsip formulir cuti secara aman.</p>
                </div>

                <form class="rounded-3xl bg-white p-6 shadow-[0_24px_60px_rgba(0,0,0,0.28)] sm:p-7" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl bg-rose-50 px-3.5 py-3 text-sm text-rose-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>
                    <div class="mt-4">
                        <label class="form-label" for="password">Kata sandi</label>
                        <input class="form-input" id="password" type="password" name="password" autocomplete="current-password" required>
                    </div>
                    <label class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm text-slate-600">
                        <input class="size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" type="checkbox" name="remember" value="1">
                        Ingat saya di perangkat ini
                    </label>
                    <button class="btn-primary mt-6 w-full" type="submit">Masuk</button>
                </form>
            </div>
        </main>
    </body>
</html>
