@if ($tab === 'apel')
    <p class="mt-2 text-sm lg:text-xs print:text-xs font-semibold text-sky-700">{{ $schedule->type === 'upacara' ? 'Upacara' : 'Apel' }} · Kelompok {{ $schedule->ceremony_group_number ?? '-' }}</p>
    <p class="mt-1 break-words text-sm lg:text-xs print:text-xs text-slate-600">{{ $schedule->department?->name ?? 'Belum dipilih' }}</p>
@else
    <p class="mt-2 text-sm lg:text-xs print:text-xs font-semibold text-sky-700">Kelompok {{ data_get($schedule->duty_roster, 'number', $schedule->dutyGroup?->number ?? '-') }}</p>
    <p class="mt-1 break-words text-sm lg:text-xs print:text-xs font-semibold text-slate-700">{{ data_get($schedule->duty_roster, 'coordinator', 'Belum dipilih') }}</p>
    @if (data_get($schedule->duty_roster, 'members', []))
        <ol class="mt-1 list-decimal space-y-0.5 pl-4 break-words text-sm leading-relaxed lg:text-[11px] lg:leading-4 print:text-[11px] text-slate-600">
            @foreach (data_get($schedule->duty_roster, 'members', []) as $member)
                <li>{{ $member }}</li>
            @endforeach
        </ol>
    @endif
@endif
