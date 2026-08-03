<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveRequest */
class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'request_number' => $this->request_number,
            'status' => $this->status,
            'employee' => [
                'id' => $this->employee?->id,
                'nip' => $this->employee?->nip,
                'full_name' => $this->employee?->full_name,
                'department' => $this->employee?->department?->name,
                'position' => $this->employee?->position?->name ?? $this->employee?->position_title,
            ],
            'leave_type' => [
                'code' => $this->leaveType?->code,
                'name' => $this->leaveType?->name,
            ],
            'form_date' => $this->form_date?->toDateString(),
            'reason' => $this->reason,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'duration' => [
                'value' => $this->duration_value,
                'unit' => $this->duration_unit,
            ],
            'generated_at' => $this->generated_at?->toAtomString(),
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
