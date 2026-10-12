<nav class="flex flex-col gap-2" aria-label="Jenis jadwal publik">
    <a class="{{ ! $activeTab ? 'btn-primary' : 'btn-secondary' }} w-full justify-start" href="{{ route('public-schedules.index') }}" @if (! $activeTab) aria-current="page" @endif>Semua jenis jadwal</a>
    @foreach (['apel' => 'Apel / Upacara', 'piket' => 'Piket Kecamatan', 'kelurahan' => 'Piket Kelurahan'] as $value => $label)
        <a class="{{ $activeTab === $value ? 'btn-primary' : 'btn-secondary' }} w-full justify-start" href="{{ route('public-schedules.index', ['tab' => $value]) }}" @if ($activeTab === $value) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
