<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EmployeeImportColumns
{
    private const API_FIELDS = [
        'nip' => ['nip'],
        'full_name' => ['full_name'],
        'employee_name' => ['full_name'],
        'position_title' => ['position.title'],
        'department_name' => ['department.name'],
        'phone' => ['phone'],
        'email' => ['email'],
        'nik' => ['sensitive.nik'],
        'npwp' => ['sensitive.npwp'],
        'birth_date' => ['birth_date'],
        'gender' => ['gender'],
        'position_type' => ['position_type'],
        'eselon' => ['eselon'],
        'employment_status' => ['employment_status'],
        'rank_name' => ['rank.name'],
        'grade' => ['rank.grade'],
        'marital_status' => ['marital_status'],
        'spouse_count' => ['spouse_count'],
        'child_count' => ['child_count'],
        'spouse_is_pns' => ['spouse_is_pns'],
        'spouse_nip' => ['sensitive.spouse_nip'],
        'grade_service_years' => ['grade_service.years', 'grade_service.months'],
        'address' => ['address'],
        'bank_code' => ['sensitive.bank_accounts.bank_code'],
        'bank_name' => ['sensitive.bank_accounts.bank_name'],
        'account_number' => ['sensitive.bank_accounts.account_number'],
    ];

    /** @return list<string> */
    public static function fromHeaderMap(array $headerMap): array
    {
        $fields = ['nip'];
        foreach (self::API_FIELDS as $column => $paths) {
            if (isset($headerMap[$column])) {
                array_push($fields, ...$paths);
            }
        }

        return array_values(array_unique($fields));
    }

    public static function project(array $data, array $fields): array
    {
        $result = ['nip' => $data['nip']];
        $bankFields = [];
        foreach ($fields as $path) {
            if (str_starts_with($path, 'sensitive.bank_accounts.')) {
                $bankFields[] = substr($path, strlen('sensitive.bank_accounts.'));
            } elseif (Arr::has($data, $path)) {
                Arr::set($result, $path, Arr::get($data, $path));
            }
        }
        if ($bankFields !== [] && isset($data['sensitive']['bank_accounts'])) {
            $accounts = $data['sensitive']['bank_accounts'];
            if (is_array($accounts) || $accounts instanceof Collection) {
                $result['sensitive']['bank_accounts'] = collect($accounts)
                    ->map(fn (array $account) => Arr::only($account, $bankFields))->values()->all();
            }
        }

        return $result;
    }
}
