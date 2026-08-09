<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EmployeeSalaryHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmployeeSalaryHistory */
class EmployeeSalaryHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rankHistory = $this->rankHistory;

        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee?->id,
                'nip' => $this->employee?->nip,
                'full_name' => $this->employee?->full_name,
            ],
            'basic_salary' => $this->basic_salary,
            'effective_on' => $this->effective_on?->toDateString(),
            'change_reason' => [
                'code' => $this->change_reason,
                'label' => EmployeeSalaryHistory::reasonOptions()[$this->change_reason] ?? $this->change_reason,
            ],
            'rank' => $rankHistory === null ? null : [
                'id' => $rankHistory->id,
                'name' => $rankHistory->rank_name,
                'grade' => $rankHistory->grade,
                'effective_on' => $rankHistory->effective_on?->toDateString(),
            ],
            'decree_number' => $this->decree_number,
            'notes' => $this->notes,
            'recorded_by' => $this->createdBy?->name,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
