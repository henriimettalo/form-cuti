<?php

namespace App\Services;

use App\Models\CeremonySchedule;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CounterDutyScheduleService
{
    public function preview(array $input): array
    {
        $start = CarbonImmutable::parse($input['start_date'], 'Asia/Pontianak')->startOfDay();
        $end = CarbonImmutable::parse($input['end_date'], 'Asia/Pontianak')->startOfDay();
        $groups = DutyGroup::query()->orderBy('number')->get();
        $previous = CeremonySchedule::query()->where('type', 'piket_loket')
            ->whereDate('event_date', '<', $start->toDateString())->orderByDesc('event_date')->orderByDesc('id')->first();

        if ($previous && ! $previous->duty_roster) {
            throw ValidationException::withMessages(['start_date' => 'Tentukan kelompok pada jadwal piket sebelumnya ('.$previous->event_date->format('d/m/Y').') sebelum melanjutkan rotasi.']);
        }

        $nextNumber = $previous ? ($previous->duty_roster['number'] % $groups->count()) + 1 : (int) $input['starting_group'];
        $holidays = DutyHoliday::query()->whereDate('holiday_date', '>=', $start->toDateString())->whereDate('holiday_date', '<=', $end->toDateString())
            ->get()->keyBy(fn ($holiday) => $holiday->holiday_date->toDateString());
        $existing = CeremonySchedule::query()->where('type', 'piket_loket')
            ->whereDate('event_date', '>=', $start->toDateString())->whereDate('event_date', '<=', $end->toDateString())->get()
            ->groupBy(fn ($schedule) => $schedule->event_date->toDateString());
        $rows = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dateKey = $date->toDateString();
            $appointments = $existing->get($dateKey);

            if ($appointments) {
                if ($appointments->count() !== 1 || ! $appointments->first()->duty_roster) {
                    throw ValidationException::withMessages(['start_date' => 'Periksa jadwal piket tanggal '.$date->format('d/m/Y').': harus satu jadwal dengan kelompok yang sudah ditentukan.']);
                }

                $appointment = $appointments->first();
                $rows[] = ['date' => $dateKey, 'status' => 'existing', 'roster' => $appointment->duty_roster];
                $nextNumber = ($appointment->duty_roster['number'] % $groups->count()) + 1;

                continue;
            }

            if ($date->isWeekend() || $holidays->has($dateKey)) {
                $rows[] = ['date' => $dateKey, 'status' => 'holiday', 'reason' => $holidays->get($dateKey)?->name ?? 'Akhir pekan'];

                continue;
            }

            $group = $groups->firstWhere('number', $nextNumber);
            $rows[] = ['date' => $dateKey, 'status' => 'new', 'group_id' => $group->id, 'roster' => $group->roster()];
            $nextNumber = ($nextNumber % $groups->count()) + 1;
        }

        $newRows = array_filter($rows, fn ($row) => $row['status'] === 'new');
        if ($newRows !== []) {
            $lastNewDate = max(array_column($newRows, 'date'));
            if (CeremonySchedule::query()->where('type', 'piket_loket')->whereDate('event_date', '>', $lastNewDate)->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Sudah ada jadwal setelah rentang ini. Tambahkan jadwal setelah jadwal terakhir agar urutan rotasi tidak berubah.']);
            }
        }

        return ['input' => $input, 'continued' => $previous !== null, 'rows' => $rows];
    }

    public function store(array $preview, int $userId): int
    {
        try {
            return DB::transaction(function () use ($preview, $userId): int {
                DutyGroup::query()->orderBy('id')->lockForUpdate()->get();
                $current = $this->preview($preview['input']);

                if ($current !== $preview) {
                    throw ValidationException::withMessages(['preview' => 'Kelompok, hari libur, atau jadwal telah berubah. Buat pratinjau ulang sebelum menyimpan.']);
                }

                $count = 0;
                foreach ($current['rows'] as $row) {
                    if ($row['status'] !== 'new') {
                        continue;
                    }

                    CeremonySchedule::query()->create([
                        'title' => 'Piket Loket — Kelompok '.$row['roster']['number'],
                        'type' => 'piket_loket',
                        'event_date' => $row['date'],
                        'duty_date' => $row['date'],
                        'duty_group_id' => $row['group_id'],
                        'duty_roster' => $row['roster'],
                        'created_by' => $userId,
                    ]);
                    $count++;
                }

                return $count;
            });
        } catch (QueryException $exception) {
            if (! ($exception instanceof UniqueConstraintViolationException)) {
                throw $exception;
            }

            throw ValidationException::withMessages(['preview' => 'Jadwal piket sudah tersimpan oleh proses lain. Buat pratinjau ulang; tidak ada jadwal yang ditimpa.']);
        }
    }
}
