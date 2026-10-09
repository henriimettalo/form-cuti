<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CeremonyScheduleResource;
use App\Models\CeremonySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    public function ceremonies(Request $request): AnonymousResourceCollection
    {
        return $this->schedules($request, ['apel', 'upacara']);
    }

    public function counterDuty(Request $request): AnonymousResourceCollection
    {
        return $this-> schedules($request, ['piket_loket']);
    }

    /**
     * @param array<int, string> $types
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
            ->when($data['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('event_date', '>=', $date))
            ->when($data['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('event_date', '<=', $date))
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->paginate($data['per_page'] ?? config('api.pagination.default'))
            ->withQueryString();

        return CeremonyScheduleResource::collection($schedules);
    }
}
