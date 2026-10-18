<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CeremonyScheduleResource;
use App\Models\CeremonySchedule;
use App\Models\KelurahanDutySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ScheduleController extends Controller
{
    public function ceremonies(Request $request): AnonymousResourceCollection
    {
        return $this->schedules($request, ['apel', 'upacara']);
    }

    public function counterDuty(Request $request): AnonymousResourceCollection
    {
        return $this->schedules($request, ['piket_loket']);
    }

    public function counterDutyReminder(Request $request): JsonResponse
    {
        $date = $this->reminderDate($request);
        $today = CarbonImmutable::today('Asia/Pontianak');
        $isToday = $date->isSameDay($today);
        $label = $isToday ? 'Hari Ini' : $date->locale('id')->translatedFormat('j F Y');
        $schedules = CeremonySchedule::query()->with('dutyGroup')
            ->where('type', 'piket_loket')->whereNull('department_id')
            ->where('event_date', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama'))
            ->orderBy('start_time')->orderBy('id')->get();
        $data = $schedules->map(function (CeremonySchedule $schedule) use ($isToday, $label): array {
            $roster = $schedule->duty_roster ?? $schedule->dutyGroup?->roster();
            $number = $roster['number'] ?? $schedule->dutyGroup?->number;
            $coordinator = $roster['coordinator'] ?? 'Belum ditentukan';
            $members = $roster['members'] ?? [];
            $intro = $isToday ? 'Hari ini' : 'Pada '.$label;
            $message = '🔔 Reminder Jadwal Piket '.$label." 🔔\n\n"
                .$intro.' giliran kelompok '.($number ?? 'belum ditentukan').":\n"
                .'Penanggung Jawab: '.$coordinator."\nPetugas Piket:\n"
                .($members ? implode("\n", $members) : 'Belum ditentukan')
                ."\n\nJangan lupa ya, terima kasih!";

            return ['id' => $schedule->id, 'group_number' => $number, 'coordinator' => $coordinator, 'members' => $members, 'message' => $message];
        });

        return $this->reminderResponse($date, $data, 'piket kecamatan');
    }

    public function ceremonyReminder(Request $request): JsonResponse
    {
        $date = $this->reminderDate($request);
        $label = $date->isSameDay(CarbonImmutable::today('Asia/Pontianak')) ? 'Hari Ini' : $date->locale('id')->translatedFormat('j F Y');
        $data = CeremonySchedule::query()->with('department')->whereIn('type', ['apel', 'upacara'])
            ->where('event_date', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama'))
            ->orderBy('start_time')->orderBy('id')->get()
            ->map(function (CeremonySchedule $schedule) use ($label): array {
                $department = $schedule->department?->name ?? 'Belum ditentukan';
                $message = '🔔 Reminder Jadwal Apel / Upacara '.$label." 🔔\n\n"
                    .'Kelompok: '.($schedule->ceremony_group_number ?? 'Belum ditentukan')."\n"
                    .'Unit Petugas: '.$department."\n"
                    .'Pemimpin: '.($schedule->leader ?: 'Belum ditentukan')."\n"
                    .'Waktu: '.($schedule->start_time ?: 'Belum ditentukan')."\n"
                    .'Lokasi: '.($schedule->location ?: 'Belum ditentukan')
                    ."\n\nJangan lupa ya, terima kasih!";

                return [
                    'id' => $schedule->id, 'group_number' => $schedule->ceremony_group_number,
                    'department' => ['id' => $schedule->department_id, 'name' => $schedule->department?->name],
                    'leader' => $schedule->leader, 'start_time' => $schedule->start_time,
                    'location' => $schedule->location, 'message' => $message,
                ];
            });

        return $this->reminderResponse($date, $data, 'apel/upacara');
    }

    public function kelurahanReminder(Request $request): JsonResponse
    {
        $date = $this->reminderDate($request);
        $label = $date->isSameDay(CarbonImmutable::today('Asia/Pontianak')) ? 'Hari Ini' : $date->locale('id')->translatedFormat('j F Y');
        $data = KelurahanDutySchedule::query()->with('department')
            ->where('event_date', $date->toDateString())
            ->whereHas('department', fn ($query) => $query->where('department_type', 'kelurahan')->where('is_active', true))
            ->orderBy('department_id')->orderBy('group_number')->orderBy('id')->get()
            ->map(function (KelurahanDutySchedule $schedule) use ($label): array {
                $message = '🔔 Reminder Jadwal Piket Kelurahan '.$label." 🔔\n\n"
                    .$schedule->department->name."\nKelompok: ".$schedule->group_number."\n"
                    .'Penanggung Jawab: '.$schedule->coordinator."\nPetugas Piket:\n"
                    .($schedule->members ? implode("\n", $schedule->members) : 'Belum ditentukan')
                    ."\n\nJangan lupa ya, terima kasih!";

                return [
                    'id' => $schedule->id, 'group_number' => $schedule->group_number,
                    'department' => ['id' => $schedule->department_id, 'name' => $schedule->department->name],
                    'coordinator' => $schedule->coordinator, 'members' => $schedule->members ?? [], 'message' => $message,
                ];
            });

        return $this->reminderResponse($date, $data, 'piket kelurahan');
    }

    private function reminderDate(Request $request): CarbonImmutable
    {
        $input = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return isset($input['date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $input['date'], 'Asia/Pontianak')
            : CarbonImmutable::today('Asia/Pontianak');
    }

    private function reminderResponse(CarbonImmutable $date, Collection $data, string $type): JsonResponse
    {
        return response()->json([
            'date' => $date->toDateString(),
            'timezone' => 'Asia/Pontianak',
            'has_schedule' => $data->isNotEmpty(),
            'isAvailable' => $data->isNotEmpty() ? 1 : 0,
            'data' => $data,
            'message' => $data->isEmpty()
                ? 'Tidak ada jadwal '.$type.' pada '.$date->locale('id')->translatedFormat('j F Y').'.'
                : $data->pluck('message')->implode("\n\n"),
        ]);
    }

    /**
     * @param  array<int, string>  $types
     */
    private function schedules(Request $request, array $types): AnonymousResourceCollection
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('api.pagination.max')],
        ]);

        $schedules = CeremonySchedule::query()
            ->with(['department', 'dutyGroup'])
            ->whereIn('type', $types)
            ->when($data['month'] ?? null, function ($query, string $month): void {
                $period = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Pontianak');
                $query->whereBetween('event_date', [$period->toDateString(), $period->endOfMonth()->toDateString()]);
            })
            ->when($data['date_from'] ?? null, fn ($query, string $date) => $query->where('event_date', '>=', $date))
            ->when($data['date_to'] ?? null, fn ($query, string $date) => $query->where('event_date', '<=', $date))
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->paginate($data['per_page'] ?? config('api.pagination.default'))
            ->withQueryString();

        return CeremonyScheduleResource::collection($schedules);
    }
}
