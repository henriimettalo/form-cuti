@props([
    'id',
    'name' => 'month',
    'value',
    'years' => range(2026, 2030),
    'yearName' => null,
    'monthName' => null,
    'autoSubmit' => false,
])
@php
    $selectedMonth = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $value, 'Asia/Pontianak');
    $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
@endphp
<div class="month-picker" data-month-picker data-month-value="{{ $value }}" data-month-picker-auto-submit="{{ $autoSubmit ? 'true' : 'false' }}">
    <button class="form-input month-picker-trigger" id="{{ $id }}-trigger" type="button" data-month-picker-trigger aria-haspopup="dialog" aria-expanded="false">
        <span data-month-picker-label>{{ $selectedMonth->locale('id')->translatedFormat('F Y') }}</span>
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
    </button>
    <div class="month-picker-popover" data-month-picker-popover hidden>
        <div class="month-picker-header">
            <span class="text-sm font-semibold text-slate-800">Pilih bulan</span>
            <select class="month-picker-year" data-month-picker-year aria-label="Tahun">
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((int) $selectedMonth->year === (int) $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="month-picker-grid" role="listbox" aria-label="Bulan">
            @foreach (range(1, 12) as $monthNumber)
                <button type="button" class="month-picker-option" data-month-picker-month="{{ str_pad($monthNumber, 2, '0', STR_PAD_LEFT) }}" role="option" aria-label="{{ $monthLabels[$monthNumber - 1] }}">{{ $monthLabels[$monthNumber - 1] }}</button>
            @endforeach
        </div>
    </div>
    @if ($yearName && $monthName)
        <input type="hidden" name="{{ $yearName }}" value="{{ $selectedMonth->year }}" data-month-picker-year-value>
        <input type="hidden" name="{{ $monthName }}" value="{{ $selectedMonth->month }}" data-month-picker-month-value>
    @else
        <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" data-month-picker-value>
    @endif
</div>
