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
                        <input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required autofocus>
                    </div>
                    <div class="mt-4">
                        <label class="form-label" for="password">Kata sandi</label>
                        <div class="relative">
                            <input class="form-input pr-12" id="password" type="password" name="password" autocomplete="current-password" required>
                            <button class="absolute right-1 top-1/2 inline-flex size-11 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600" type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi">
                                <svg data-password-show aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.5 5.25 12 5.25 21.75 12 21.75 12 18.5 18.75 12 18.75 2.25 12 2.25 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-password-hide aria-hidden="true" class="hidden size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M9.75 5.5A11 11 0 0 1 12 5.25c6.5 0 9.75 6.75 9.75 6.75a17 17 0 0 1-3.5 4.5M6.25 6.75A17 17 0 0 0 2.25 12S5.5 18.75 12 18.75a11 11 0 0 0 4.25-.85M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </div>
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
