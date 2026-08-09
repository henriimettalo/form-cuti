<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayroll extends Model
{
    use HasFactory;

    public const RECONCILIATION_MATCHED = 'matched';

    public const RECONCILIATION_MISMATCH = 'mismatch';

    public const TPP_RECONCILIATION_PENDING = 'pending';

    public const TPP_RECONCILIATION_MATCHED = 'matched';

    public const TPP_RECONCILIATION_MISMATCH = 'mismatch';

    /** @var list<string> */
    public const COMPONENTS = [
        'basic_salary',
        'spouse_allowance',
        'child_allowance',
        'family_allowance',
        'position_allowance',
        'functional_allowance',
        'general_functional_allowance',
        'rice_allowance',
        'pph_allowance',
        'rounding',
        'health_contribution',
        'work_accident_contribution',
        'death_contribution',
        'tapera',
        'pension_contribution',
        'papua_special_allowance',
        'jht_allowance',
        'iwp_deduction',
        'pph21_deduction',
        'zakat',
        'bulog',
    ];

    /** @var list<string> */
    public const TPP_COMPONENTS = [
        'tpp_workload',
        'tpp_workplace',
        'tpp_work_conditions',
        'tpp_profession_scarcity',
        'tpp_performance',
        'tpp_pph_allowance',
        'tpp_health_contribution',
        'tpp_work_accident_contribution',
        'tpp_death_contribution',
        'tpp_tapera',
        'tpp_pension_contribution',
        'tpp_jht_allowance',
        'tpp_iwp_deduction',
        'tpp_pph21_deduction',
        'tpp_zakat',
        'tpp_bulog',
    ];

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'employee_nip',
        'employee_name',
        'skpd_snapshot',
        'employee_bank_account_id',
        'bank_code',
        'bank_name',
        'account_number',
        ...self::COMPONENTS,
        ...self::TPP_COMPONENTS,
        'total_earnings',
        'total_deductions',
        'transferred_amount',
        'source_total_earnings',
        'source_total_deductions',
        'source_transferred_amount',
        'reconciliation_status',
        'source_row_number',
        'source_row_checksum',
        'tpp_bank_code',
        'tpp_bank_name',
        'tpp_account_number',
        'tpp_total',
        'tpp_total_deductions',
        'tpp_transferred_amount',
        'tpp_source_total',
        'tpp_source_total_deductions',
        'tpp_source_transferred_amount',
        'tpp_reconciliation_status',
        'tpp_source_row_number',
        'tpp_source_row_checksum',
    ];

    protected function casts(): array
    {
        return array_fill_keys([
            ...self::COMPONENTS,
            ...self::TPP_COMPONENTS,
            'total_earnings',
            'total_deductions',
            'source_total_earnings',
            'source_total_deductions',
            'tpp_total',
            'tpp_total_deductions',
            'tpp_source_total',
            'tpp_source_total_deductions',
        ], 'integer') + [
            'transferred_amount' => 'integer',
            'source_transferred_amount' => 'integer',
            'source_row_number' => 'integer',
            'tpp_transferred_amount' => 'integer',
            'tpp_source_transferred_amount' => 'integer',
            'tpp_source_row_number' => 'integer',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(EmployeeBankAccount::class, 'employee_bank_account_id');
    }

    /**
     * Recalculate totals from normalized components.
     * Family allowance is derived from spouse + child. Employer-paid health,
     * work-accident, and death contributions are included in gross earnings
     * and also retained in deductions to match the source payroll convention.
     *
     * @param  array<string, mixed>  $data
     * @return array{family_allowance: int, total_earnings: int, total_deductions: int, transferred_amount: int}
     */
    public static function calculatedTotals(array $data): array
    {
        $money = static fn (string $key): int => max(0, (int) ($data[$key] ?? 0));
        $familyAllowance = $money('spouse_allowance') + $money('child_allowance');
        $totalEarnings = $money('basic_salary')
            + $familyAllowance
            + $money('position_allowance')
            + $money('functional_allowance')
            + $money('general_functional_allowance')
            + $money('rice_allowance')
            + $money('pph_allowance')
            + $money('rounding')
            + $money('papua_special_allowance')
            + $money('health_contribution')
            + $money('work_accident_contribution')
            + $money('death_contribution');
        $totalDeductions = $money('health_contribution')
            + $money('work_accident_contribution')
            + $money('death_contribution')
            + $money('tapera')
            + $money('pension_contribution')
            + $money('jht_allowance')
            + $money('iwp_deduction')
            + $money('pph21_deduction')
            + $money('zakat')
            + $money('bulog');

        return [
            'family_allowance' => $familyAllowance,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'transferred_amount' => $totalEarnings - $totalDeductions,
        ];
    }

    /**
     * Recalculate TPP totals from TPP-specific components.
     *
     * @param  array<string, mixed>  $data
     * @return array{tpp_total: int, tpp_total_deductions: int, tpp_transferred_amount: int}
     */
    public static function calculatedTppTotals(array $data): array
    {
        $money = static fn (string $key): int => max(0, (int) ($data[$key] ?? 0));
        $tppTotal = $money('tpp_workload')
            + $money('tpp_workplace')
            + $money('tpp_work_conditions')
            + $money('tpp_profession_scarcity')
            + $money('tpp_performance')
            + $money('tpp_pph_allowance')
            + $money('tpp_health_contribution')
            + $money('tpp_work_accident_contribution')
            + $money('tpp_death_contribution');
        $tppDeductions = $money('tpp_health_contribution')
            + $money('tpp_work_accident_contribution')
            + $money('tpp_death_contribution')
            + $money('tpp_tapera')
            + $money('tpp_pension_contribution')
            + $money('tpp_jht_allowance')
            + $money('tpp_iwp_deduction')
            + $money('tpp_pph21_deduction')
            + $money('tpp_zakat')
            + $money('tpp_bulog');

        return [
            'tpp_total' => $tppTotal,
            'tpp_total_deductions' => $tppDeductions,
            'tpp_transferred_amount' => $tppTotal - $tppDeductions,
        ];
    }
}
