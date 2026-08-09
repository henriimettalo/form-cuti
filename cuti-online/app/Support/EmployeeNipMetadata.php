<?php

namespace App\Support;

use DateTimeImmutable;

final class EmployeeNipMetadata
{
    /**
     * @return array{birth_date: ?string, gender: ?string, service_started_on: ?string, tmt_valid: ?bool}
     */
    public static function derive(?string $nip): array
    {
        $digits = self::digits($nip);
        $birthDate = null;
        $serviceStartedOn = null;
        $tmtValid = null;

        if (strlen($digits) >= 8) {
            $birthDate = self::validDate(substr($digits, 0, 8));
        }

        if (strlen($digits) >= 14) {
            $year = substr($digits, 8, 4);
            $month = (int) substr($digits, 12, 2);
            $tmtValid = $month >= 1 && $month <= 12;

            if ($tmtValid) {
                $serviceStartedOn = sprintf('%s-%02d-01', $year, $month);
            }
        }

        return [
            'birth_date' => $birthDate,
            'gender' => strlen($digits) >= 15
                ? match (substr($digits, 14, 1)) {
                    '1' => 'L',
                    '2' => 'P',
                    default => null,
                }
                : null,
            'service_started_on' => $serviceStartedOn,
            'tmt_valid' => $tmtValid,
        ];
    }

    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private static function validDate(string $value): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!Ymd', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
