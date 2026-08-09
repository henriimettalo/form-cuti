<?php

namespace App\Http\Requests\Api\V1;

use App\Support\EmployeeNipMetadata;
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
        $derived = EmployeeNipMetadata::derive($this->input('nip'));

        $this->merge(array_filter([
            'rank_grade' => $rank['grade'] ?? null,
            'nik' => $this->digitsOrNull($this->input('nik')),
            'npwp' => $this->digitsOrNull($this->input('npwp')),
            'spouse_nip' => $this->digitsOrNull($this->input('spouse_nip')),
            'gender' => $this->input('gender') ?: $derived['gender'],
            'birth_date' => $this->input('birth_date') ?: $derived['birth_date'],
            'service_started_on' => $this->input('service_started_on') ?: $derived['service_started_on'],
            'eselon' => $this->input('eselon') ?: '00',
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:32', Rule::unique('employees', 'nip')],
            'nik' => ['nullable', 'digits:16', Rule::unique('employees', 'nik')->whereNotNull('nik')],
            'npwp' => ['nullable', 'digits_between:15,16'],
            'full_name' => ['required', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'position_type' => ['nullable', 'integer', Rule::in([1, 3])],
            'eselon' => ['nullable', 'string', 'max:8', 'regex:/^[0-9A-Za-z\/-]+$/'],
            'rank_grade' => [
                'nullable',
                Rule::requiredIf($this->input('employment_status') === 'PNS'),
                Rule::in(array_keys($this->rankOptions())),
            ],
            'employment_status' => ['required', 'string', 'max:32'],
            'marital_status' => ['nullable', 'integer', Rule::in([1, 2])],
            'spouse_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            'spouse_is_pns' => ['nullable', 'boolean'],
            'spouse_nip' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'service_started_on' => ['nullable', 'date'],
            'grade_service_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'grade_service_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
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
        $data['spouse_count'] = (int) ($data['spouse_count'] ?? 0);
        $data['child_count'] = (int) ($data['child_count'] ?? 0);
        $data['eselon'] = $data['eselon'] ?? '00';
        $data['nip_tmt_valid'] = EmployeeNipMetadata::derive($data['nip'])['tmt_valid'];

        return $data;
    }

    /**
     * @return array<string, array{name: string, grade: string}>
     */
    private function rankOptions(): array
    {
        return EmployeeRankOptions::optionsFor($this->input('employment_status'));
    }

    private function digitsOrNull(mixed $value): ?string
    {
        $digits = EmployeeNipMetadata::digits($value === null ? null : (string) $value);

        return $digits === '' ? null : $digits;
    }
}
