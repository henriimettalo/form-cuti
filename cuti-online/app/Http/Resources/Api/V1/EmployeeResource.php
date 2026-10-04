<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Employee;
use App\Support\EmployeeImportColumns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentPositionHistory = $this->positionHistories->firstWhere('department_name');
        $canViewSensitive = $request->user()?->tokenCan('employees:profile') === true;

        $data = [
            'id' => $this->id,
            'nip' => $this->nip,
            'full_name' => $this->full_name,
            'employment_status' => $this->employment_status,
            'rank' => [
                'name' => $this->rank_name,
                'grade' => $this->grade,
            ],
            'position' => [
                'id' => $this->position?->id,
                'title' => $this->position?->name ?? $this->position_title,
            ],
            'department' => [
                'id' => $this->department?->id ?? $currentPositionHistory?->department_id,
                'name' => $this->department?->name ?? $currentPositionHistory?->department_name,
            ],
            'service_started_on' => $this->service_started_on?->toDateString(),
            'nip_tmt_valid' => $this->nip_tmt_valid,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'position_type' => $this->position_type,
            'position_type_label' => Employee::POSITION_TYPES[$this->position_type] ?? null,
            'eselon' => $this->eselon,
            'marital_status' => $this->marital_status,
            'marital_status_label' => [
                1 => 'Menikah',
                2 => 'Belum menikah',
            ][$this->marital_status] ?? null,
            'spouse_count' => $this->spouse_count,
            'child_count' => $this->child_count,
            'dependents_count' => $this->spouse_count + $this->child_count,
            'spouse_is_pns' => $this->spouse_is_pns,
            'grade_service' => [
                'years' => $this->grade_service_years,
                'months' => $this->grade_service_months,
            ],
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];

        if ($canViewSensitive) {
            $data['sensitive'] = [
                'nik' => $this->nik,
                'npwp' => $this->npwp,
                'spouse_nip' => $this->spouse_nip,
                'bank_accounts' => $this->whenLoaded(
                    'bankAccounts',
                    fn () => $this->bankAccounts->map(static fn ($account): array => [
                        'bank_code' => $account->bank_code,
                        'bank_name' => $account->bank_name,
                        'bank_id' => $account->bank_id,
                        'account_number' => $account->account_number,
                        'account_holder_name' => $account->account_holder_name,
                        'is_primary' => $account->is_primary,
                    ])->values(),
                ),
            ];
        }

        return $request->query('view') === 'imported'
            ? EmployeeImportColumns::project($data, $this->imported_api_fields ?? [])
            : $data;
    }
}
