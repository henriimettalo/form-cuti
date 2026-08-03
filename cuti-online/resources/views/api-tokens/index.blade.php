@extends('layouts.app')

@section('title', 'Integrasi API')

@section('content')
    @php($plainTextToken = session('apiTokenPlainText'))
    @php($selectedAbilities = old('abilities', ['employees:read', 'leave-requests:read']))

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Integrasi</p>
            <h1 class="page-title mt-2">Integrasi API</h1>
            <p class="page-description">Buat token untuk menghubungkan SIMPEG dengan sistem lain melalui REST API versi 1.</p>
        </div>
    </div>

    @if ($plainTextToken)
        <section class="card mt-7 shadow-[0_14px_30px_rgba(16,185,129,0.12)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Token baru</p>
                    <h2 class="section-heading mt-2">Salin token ini sekarang</h2>
                    <p class="section-description">Demi keamanan, token lengkap tidak akan ditampilkan lagi setelah halaman ini ditutup atau dimuat ulang.</p>
                </div>
                <span class="status-generated shrink-0">Tampilkan sekali</span>
            </div>
            <code class="mt-5 block break-all rounded-xl bg-slate-950 px-4 py-3 font-mono text-xs leading-6 text-slate-100 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.1)]">{{ $plainTextToken }}</code>
        </section>
    @endif

    <div class="mt-7 grid gap-7 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,0.78fr)]">
        <section class="card">
            <div>
                <h2 class="section-heading">Buat token baru</h2>
                <p class="section-description">Beri akses secukupnya untuk sistem yang akan terhubung.</p>
            </div>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('api-tokens.store') }}">
                @csrf

                <div>
                    <label class="form-label" for="name">Nama integrasi</label>
                    <input class="form-input" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Sistem Absensi" required>
                    <p class="form-help">Gunakan nama sistem atau layanan yang akan memakai token ini.</p>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="expires_at">Berlaku sampai</label>
                    <input class="form-input" id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}">
                    <p class="form-help">Opsional. Token tanpa tanggal kedaluwarsa berlaku sampai dicabut.</p>
                    @error('expires_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <fieldset>
                    <legend class="form-label">Izin token</legend>
                    <div class="mt-3 grid gap-3">
                        @foreach ($abilityOptions as $ability => $option)
                            <label class="card-inner flex min-h-11 cursor-pointer items-start gap-3 transition-colors hover:bg-slate-100">
                                <input class="mt-0.5 size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" name="abilities[]" type="checkbox" value="{{ $ability }}" @checked(in_array($ability, $selectedAbilities, true))>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">{{ $option['label'] }}</span>
                                    <span class="mt-0.5 block text-sm leading-5 text-slate-500">{{ $option['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('abilities') <p class="form-error">{{ $message }}</p> @enderror
                    @error('abilities.*') <p class="form-error">{{ $message }}</p> @enderror
                </fieldset>

                <div class="flex justify-end border-t border-slate-100 pt-6">
                    <button class="btn-primary" type="submit">
                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Buat token API
                    </button>
                </div>
            </form>
        </section>

        <section class="card">
            <div>
                <h2 class="section-heading">Cara memakai</h2>
                <p class="section-description">Kirim token pada setiap permintaan sebagai Bearer token.</p>
            </div>

            <div class="card-inner mt-6">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Base URL</p>
                <code class="mt-2 block break-all font-mono text-sm font-semibold text-slate-800">{{ $apiBaseUrl }}</code>
            </div>

            <div class="mt-5 grid gap-3 text-sm">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-mono font-semibold text-slate-800">GET {{ $apiBaseUrl }}/employees</p>
                    <p class="mt-1 leading-5 text-slate-500">Daftar pegawai. Izin: <code>employees:read</code>.</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-mono font-semibold text-slate-800">POST {{ $apiBaseUrl }}/employees</p>
                    <p class="mt-1 leading-5 text-slate-500">Tambah pegawai. Izin: <code>employees:write</code>.</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="font-mono font-semibold text-slate-800">GET {{ $apiBaseUrl }}/leave-requests</p>
                    <p class="mt-1 leading-5 text-slate-500">Daftar formulir cuti. Izin: <code>leave-requests:read</code>.</p>
                </div>
            </div>

            <pre class="mt-5 overflow-x-auto rounded-xl bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-100 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.1)]"><code>curl -H "Authorization: Bearer TOKEN_ANDA" \
  "{{ $apiBaseUrl }}/employees"</code></pre>
        </section>
    </div>

    <section class="card mt-7">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading">Token aktif</h2>
                <p class="section-description">Cabut token segera bila sistem eksternal tidak lagi menggunakan akses ini.</p>
            </div>
            <span class="status-draft tabular-nums">{{ number_format($tokens->count(), 0, ',', '.') }} token</span>
        </div>

        @if ($tokens->isEmpty())
            <div class="card-inner mt-5 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada token integrasi.</p>
                <p class="mt-1 text-sm text-slate-500">Buat token setelah menentukan sistem yang perlu terhubung.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Integrasi</th>
                            <th class="pb-3 pr-5 font-semibold">Izin</th>
                            <th class="pb-3 pr-5 font-semibold">Terakhir dipakai</th>
                            <th class="pb-3 pr-5 font-semibold">Berlaku sampai</th>
                            <th class="pb-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tokens as $token)
                            <tr>
                                <td class="py-4 pr-5 font-semibold text-slate-800">{{ $token->name }}</td>
                                <td class="py-4 pr-5 text-xs leading-5 text-slate-600">{{ implode(', ', $token->abilities ?? []) }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ $token->last_used_at?->format('d M Y H:i') ?? 'Belum pernah' }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ $token->expires_at?->format('d M Y') ?? 'Tidak dibatasi' }}</td>
                                <td class="py-4 text-right">
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-50 px-4 text-sm font-semibold text-rose-700 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.16)] transition-transform duration-150 hover:bg-rose-100 active:scale-[0.96]" type="submit">Cabut</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
