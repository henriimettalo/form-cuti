<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'SIMPEG') · Sistem Kepegawaian</title>
        <script>
            if (window.self !== window.top) {
                document.documentElement.dataset.workspaceFrame = 'true';
            }
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)]">
            <aside class="border-b border-slate-200 bg-white/85 px-4 py-4 backdrop-blur lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto lg:border-r lg:border-b-0 lg:px-5 lg:py-6">
                <div class="flex items-center gap-3 px-2">
                    <div class="grid size-10 place-items-center rounded-2xl bg-sky-600 text-sm font-bold tracking-tight text-white shadow-[0_8px_18px_rgba(2,132,199,0.24)]">SK</div>
                    <div>
                        <p class="text-sm font-semibold text-slate-950">SIMPEG</p>
                        <p class="text-xs text-slate-500">Sistem Kepegawaian</p>
                    </div>
                </div>

                <nav class="mt-7 flex gap-1 overflow-x-auto pb-1 lg:flex-col">
                    <p class="hidden px-2 pb-1 pt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 lg:block">Kepegawaian</p>
                    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}" href="{{ route('dashboard') }}" data-workspace-link data-workspace-title="Dashboard">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m3 13 9-9 9 9M5 11v9a1 1 0 0 0 1 1h3v-6h6v6h3a1 1 0 0 0 1-1v-9" /></svg>
                        Dashboard
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('employees.*') && ! request()->routeIs('employees.change-logs.*') ? 'sidebar-link-active' : '' }}" href="{{ route('employees.index') }}" data-workspace-link data-workspace-title="Pegawai">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19a6 6 0 0 0-12 0m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6 3a4 4 0 0 1 4 4m-4-7a3 3 0 1 0 0-6" /></svg>
                        Pegawai
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('employees.change-logs.*') ? 'sidebar-link-active' : '' }}" href="{{ route('employees.change-logs.index') }}" data-workspace-link data-workspace-title="Log Perubahan Pegawai">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Log perubahan
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('payroll.*') ? 'sidebar-link-active' : '' }}" href="{{ route('payroll.index') }}" data-workspace-link data-workspace-title="Payroll Bulanan">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V6.75Zm0 3.75h15M8.25 14.25h2.25m2.25 0H15m-6.75 3h2.25m2.25 0H15" /></svg>
                        Payroll Bulanan
                    </a>
                    <p class="hidden px-2 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 lg:block">Layanan cuti</p>
                    <a class="sidebar-link {{ request()->routeIs('leave-requests.*') ? 'sidebar-link-active' : '' }}" href="{{ route('leave-requests.index') }}" data-workspace-link data-workspace-title="Formulir Cuti">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        Formulir Cuti
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('document-templates.*') ? 'sidebar-link-active' : '' }}" href="{{ route('document-templates.index') }}" data-workspace-link data-workspace-title="Template Dokumen">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V6.75a3.375 3.375 0 0 0-3.375-3.375H7.875A3.375 3.375 0 0 0 4.5 6.75v10.5a3.375 3.375 0 0 0 3.375 3.375h4.5m3.75-3.375h.008v.008h-.008v-.008Zm-1.5 0h.008v.008h-.008v-.008Zm3 0h.008v.008h-.008v-.008Z" /></svg>
                        Template Dokumen
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('authorized-official.*') ? 'sidebar-link-active' : '' }}" href="{{ route('authorized-official.index') }}" data-workspace-link data-workspace-title="Pejabat Berwenang">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-7.5 15a7.5 7.5 0 0 1 15 0M19.5 7.5h3m-1.5-1.5v3" /></svg>
                        Pejabat Berwenang
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('organization-profile.*') ? 'sidebar-link-active' : '' }}" href="{{ route('organization-profile.index') }}" data-workspace-link data-workspace-title="Profil Instansi">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V8.25L12 3l6.75 5.25V21M9 21v-4.5h6V21M9 9.75h.008v.008H9V9.75Zm6 0h.008v.008H15V9.75Z" /></svg>
                        Profil Instansi
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('settings.*') ? 'sidebar-link-active' : '' }}" href="{{ route('settings.index') }}" data-workspace-link data-workspace-title="Pengaturan Cuti">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3m-3 12h3m4.5-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm0 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        Pengaturan cuti
                    </a>
                    <p class="hidden px-2 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 lg:block">Integrasi</p>
                    <a class="sidebar-link {{ request()->routeIs('api-tokens.*') ? 'sidebar-link-active' : '' }}" href="{{ route('api-tokens.index') }}" data-workspace-link data-workspace-title="Integrasi API">
                        <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 6.75 5.25 5.25-5.25 5.25M10.5 17.25 5.25 12l5.25-5.25" /></svg>
                        Integrasi API
                    </a>
                </nav>

                <div class="card-inner mt-7 hidden lg:block">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">SIMPEG</p>
                    <p class="mt-2 text-sm leading-5 text-slate-600">Kelola profil dan riwayat pegawai, lalu buat formulir cuti dari data yang konsisten.</p>
                </div>

                <div class="mt-6 hidden border-t border-slate-100 pt-5 lg:block">
                    <p class="px-2 text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 px-2 text-xs capitalize text-slate-500">{{ auth()->user()->role }}</p>
                    <form class="mt-3" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="sidebar-link w-full" type="submit">
                            <svg aria-hidden="true" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3-3H9m9.75 0-3-3m3 3-3 3" /></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-10 lg:py-9" data-workspace-root data-workspace-dashboard-url="{{ route('dashboard') }}">
                <div class="mx-auto max-w-6xl">
                    <div class="workspace-tabs" data-workspace-tabs>
                        <div class="workspace-tablist" role="tablist" aria-label="Halaman yang dibuka">
                            <div class="workspace-tab workspace-tab-active" data-workspace-tab data-workspace-tab-id="initial" data-workspace-url="{{ url()->full() }}">
                                <button class="workspace-tab-trigger" id="workspace-tab-initial" type="button" role="tab" aria-selected="true" aria-controls="workspace-panel-initial" data-workspace-tab-trigger data-workspace-tab-id="initial">
                                    <span class="truncate">@yield('title', 'SIMPEG')</span>
                                </button>
                                @if (! request()->routeIs('dashboard'))
                                    <button class="workspace-tab-close" type="button" aria-label="Tutup tab dan kembali ke Dashboard" title="Tutup dan kembali ke Dashboard" data-workspace-tab-close data-workspace-tab-id="initial">
                                        <span aria-hidden="true" class="text-lg leading-none">×</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <section id="workspace-panel-initial" role="tabpanel" aria-labelledby="workspace-tab-initial" data-workspace-panel data-workspace-static-panel>
                        @if (session('status'))
                            <div class="mb-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-[inset_0_0_0_1px_rgba(5,150,105,0.16)]">
                                {{ session('status') }}
                            </div>
                        @endif

                        @php
                            $errorMessages = [];

                            if (is_object($errors) && method_exists($errors, 'getBags')) {
                                foreach ($errors->getBags() as $bag) {
                                    $errorMessages = array_merge(
                                        $errorMessages,
                                        is_object($bag) && method_exists($bag, 'all')
                                            ? $bag->all()
                                            : collect($bag)->flatten()->all(),
                                    );
                                }
                            } elseif (is_object($errors) && method_exists($errors, 'all')) {
                                $errorMessages = $errors->all();
                            } else {
                                $errorMessages = collect($errors)->flatten()->all();
                            }
                        @endphp

                        @if ($errorMessages !== [])
                            <div class="mb-6 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.14)]">
                                <p class="font-semibold">Periksa kembali data yang diisi.</p>
                                <ul class="mt-1 list-inside list-disc text-rose-700">
                                    @foreach ($errorMessages as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </section>

                    <div data-workspace-panels></div>
                </div>
            </main>
        </div>
    </body>
</html>
