@extends('layouts.app')

@section('title', 'Template Dokumen')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Output formulir</p>
        <h1 class="page-title mt-2">Template dokumen</h1>
        <p class="page-description">Unggah formulir Word Anda, lalu aplikasi akan mengisi placeholder secara otomatis setiap kali formulir cuti dibuat.</p>
    </div>

    @php($activeTemplate = $templates->firstWhere('is_active', true))

    <section class="card mt-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">Template aktif</p>
                @if ($activeTemplate)
                    <p class="mt-2 truncate text-base font-semibold text-slate-900">{{ $activeTemplate->name }}</p>
                    <p class="mt-1 text-xs tabular-nums text-slate-500">Versi {{ $activeTemplate->version }}</p>
                @else
                    <p class="mt-2 text-sm leading-6 text-slate-600">Belum ada template aktif. Sementara itu, aplikasi memakai format formulir bawaan.</p>
                @endif
            </div>
            <p class="max-w-md border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-5">Dipakai untuk formulir baru. Formulir yang sudah dibuat tetap terhubung ke template saat dibuat.</p>
        </div>
    </section>

    <section class="card mt-6">
            <h2 class="section-heading">Unggah template baru</h2>
            <p class="section-description">Gunakan file Word (.docx) yang sudah berisi placeholder seperti <code class="font-semibold text-sky-700">{{ '{' . '{employee_name}' . '}' }}</code>. Template yang baru diunggah langsung menjadi aktif.</p>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('document-templates.store') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="form-label" for="name">Nama template</label>
                    <input class="form-input" id="name" name="name" value="{{ old('name') }}" maxlength="255" placeholder="Contoh: Formulir Cuti Tahunan 2026">
                    <p class="form-help">Boleh dikosongkan; nama file Word akan digunakan.</p>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div data-file-picker>
                    <label class="form-label" for="file">File template Word</label>
                    <input
                        class="peer sr-only"
                        id="file"
                        name="file"
                        type="file"
                        accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        required
                        data-file-input
                        aria-describedby="{{ $errors->has('file') ? 'file-help file-error' : 'file-help' }}"
                        aria-invalid="{{ $errors->has('file') ? 'true' : 'false' }}"
                    >
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-2xl bg-slate-100 p-1 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)] transition-shadow duration-150 hover:bg-slate-50 peer-focus-visible:bg-white peer-focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" for="file">
                        <span class="inline-flex min-h-9 shrink-0 items-center gap-2 rounded-xl bg-white px-3 text-sm font-semibold text-sky-700 shadow-[0_1px_2px_rgba(15,23,42,0.08)]">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 0 3.75 3.75M12 7.5 8.25 11.25M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                            Pilih template
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm text-slate-500" data-file-name title="Belum ada file dipilih">Belum ada file dipilih</span>
                        <svg aria-hidden="true" class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                    </label>
                    <p class="form-help" id="file-help">Menerima file .docx yang valid, maksimal 10 MB. File disimpan privat di aplikasi.</p>
                    @error('file') <p class="form-error" id="file-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <button class="btn-primary" type="submit">
                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.75-3.75M12 16.5 8.25 12.75M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                        Unggah dan aktifkan
                    </button>
                </div>
            </form>
    </section>

    <section class="card mt-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="section-heading">Template tersimpan</h2>
                <p class="section-description">Pilih kembali template lama tanpa mengubah arsip formulir yang sudah diterbitkan.</p>
            </div>
            <span class="text-sm tabular-nums text-slate-500">{{ $templates->count() }} template</span>
        </div>

        <div class="mt-5 grid gap-3">
            @forelse ($templates as $template)
                <article class="flex flex-col gap-4 rounded-xl bg-slate-50 p-4 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)] sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $template->name }}</p>
                            @if ($template->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Aktif</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs tabular-nums text-slate-500">Versi {{ $template->version }} · diunggah {{ $template->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    @if (! $template->is_active)
                        <form method="POST" action="{{ route('document-templates.activate', $template) }}">
                            @csrf
                            <button class="btn-secondary min-h-11" type="submit">Jadikan aktif</button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="card-inner text-sm leading-6 text-slate-600">Belum ada template tersimpan. Unggah file Word Anda melalui formulir di atas.</div>
            @endforelse
        </div>

        <details class="mt-6 rounded-xl bg-slate-50 p-4 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)]">
            <summary class="cursor-pointer text-sm font-semibold text-slate-800">Daftar placeholder yang dapat digunakan</summary>
            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                @foreach ($placeholderGroups as $group => $placeholders)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $group }}</p>
                        <ul class="mt-2 grid gap-2">
                            @foreach ($placeholders as $placeholder => $description)
                                <li class="text-xs leading-5 text-slate-600"><code class="font-semibold text-sky-700">{{ '{' . '{' . $placeholder . '}' . '}' }}</code> — {{ $description }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </details>
    </section>
@endsection
