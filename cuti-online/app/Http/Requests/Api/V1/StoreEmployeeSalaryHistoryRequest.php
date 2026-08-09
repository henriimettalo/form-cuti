<?php

namespace App\Http\Requests\Api\V1;

use App\Models\EmployeeSalaryHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeSalaryHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'basic_salary' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'effective_on' => ['required', 'date'],
            'change_reason' => ['required', Rule::in(array_keys(EmployeeSalaryHistory::reasonOptions()))],
            'employee_rank_history_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_rank_histories', 'id')
                    ->where(fn ($query) => $query->where('employee_id', $employee?->id)),
            ],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
