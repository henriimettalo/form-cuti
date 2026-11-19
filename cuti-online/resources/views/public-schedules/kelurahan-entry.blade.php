<p class="font-semibold text-sky-800">Kelompok {{ $schedule->group_number }}</p>
<p class="mt-1 font-medium">{{ $schedule->coordinator }}</p>
@foreach ($schedule->members ?? [] as $member)
    <p class="mt-1">{{ $member }}</p>
@endforeach
