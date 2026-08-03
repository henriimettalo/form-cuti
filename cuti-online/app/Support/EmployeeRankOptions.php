<?php

namespace App\Support;

class EmployeeRankOptions
{
    /**
     * @return list<array{label: string, ranks: list<array{name: string, grade: string, label?: string}>}>
     */
    public static function groupsFor(?string $employmentStatus): array
    {
        return match (strtoupper(trim((string) $employmentStatus))) {
            'PNS' => config('pns.rank_groups', []),
            'PPPK' => config('pppk.rank_groups', []),
            default => [],
        };
    }

    /**
     * @return array<string, array{name: string, grade: string, label?: string}>
     */
    public static function optionsFor(?string $employmentStatus): array
    {
        return collect(self::groupsFor($employmentStatus))
            ->flatMap(static fn (array $group): array => $group['ranks'])
            ->keyBy('grade')
            ->all();
    }

    /**
     * Find a rank from a dropdown value or a human-readable import value.
     *
     * @return array{name: string, grade: string, label?: string}|null
     */
    public static function find(?string $employmentStatus, ?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $options = self::optionsFor($employmentStatus);
        $value = trim($value);

        if (isset($options[$value])) {
            return $options[$value];
        }

        $grade = self::normaliseGrade($employmentStatus, $value);

        return $grade === null ? null : ($options[$grade] ?? null);
    }

    public static function format(?string $employmentStatus, ?string $rankName, ?string $grade): string
    {
        $rankName = trim((string) $rankName);
        $grade = trim((string) $grade);

        if ($rankName === '' && $grade === '') {
            return '-';
        }

        if (strtoupper(trim((string) $employmentStatus)) === 'PPPK') {
            return trim(implode(' ', array_filter([$rankName, $grade]))) ?: '-';
        }

        if ($rankName === '') {
            return $grade;
        }

        if ($grade === '') {
            return $rankName;
        }

        return "{$rankName} ({$grade})";
    }

    private static function normaliseGrade(?string $employmentStatus, string $value): ?string
    {
        return match (strtoupper(trim((string) $employmentStatus))) {
            'PNS' => self::normalisePnsGrade($value),
            'PPPK' => self::normalisePppkGrade($value),
            default => null,
        };
    }

    private static function normalisePnsGrade(string $value): ?string
    {
        if (! preg_match('/\b(IV|III|II|I)\/([A-E])\b/i', $value, $matches)) {
            return null;
        }

        return strtoupper($matches[1]).'/'.strtolower($matches[2]);
    }

    private static function normalisePppkGrade(string $value): ?string
    {
        if (! preg_match('/\b(?:GOLONGAN\s+)?(XX|XIX|XVIII|XVII|XVI|XV|XIV|XIII|XII|XI|X|IX|VIII|VII|VI|V|IV|III|II|I)\b/i', $value, $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }
}
