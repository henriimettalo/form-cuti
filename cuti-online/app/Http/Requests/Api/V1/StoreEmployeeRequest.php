<?php

namespace App\Http\Requests\Api\V1;

use App\Support\EmployeeRankOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rank = EmployeeRankOptions::find($this->input('employment_status'), $this->input('rank_grade'));

        if ($rank !== null) {
            $this->merge(['rank_grade' => $rank['grade']]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:32', Rule::unique('employees', 'nip')],
            'full_name' => ['required', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'rank_grade' => [
                'nullable',
                Rule::requiredIf($this->input('employment_status') === 'PNS'),
                Rule::in(array_keys($this->rankOptions())),
            ],
            'employment_status' => ['required', 'string', 'max:32'],
            'service_started_on' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeData(): array
    {
        $data = $this->validated();
        $rank = isset($data['rank_grade']) ? $this->rankOptions()[$data['rank_grade']] : null;

        $data['rank_name'] = $rank['name'] ?? null;
        $data['grade'] = $rank['grade'] ?? null;

        return $data;
    }

    /**
     * @return array<string, array{name: string, grade: string}>
     */
    private function rankOptions(): array
    {
        return EmployeeRankOptions::optionsFor($this->input('employment_status'));
    }
}
