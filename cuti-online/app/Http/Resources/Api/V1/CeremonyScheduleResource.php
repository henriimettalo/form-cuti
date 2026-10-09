<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CeremonySchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CeremonySchedule */
class CeremonyScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roster = $this->duty_roster;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => CeremonySchedule::normalizeType($this->type),
            'type_label' => $this->typeLabel(),
            'event_date' => $this->event_date?->toDateString(),
            'start_time' => $this->start_time,
            'location' => $this->location,
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ],
            'leader' => $this->leader,
            'notes' => $this->notes,
            'ceremony_group_number' => $this->ceremony_group_number,
            'duty_date' => $this->duty_date?->toDateString(),
            'duty_group' => $this->duty_group_id === null ? null : [
                'id' => $this->duty_group_id,
                'number' => $this->dutyGroup?->number,
            ],
            'duty_roster' => $roster,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
