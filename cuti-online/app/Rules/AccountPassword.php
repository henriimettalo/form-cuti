<?php

namespace App\Rules;

use Closure;
use Illuminate\Validation\Rules\Password;

class AccountPassword
{
    public static function rules(): array
    {
        return [
            'bail',
            'required',
            'string',
            Password::min(12),
            static function (string $attribute, string $value, Closure $fail): void {
                if (strlen($value) > 72) {
                    $fail('Kata sandi tidak boleh melebihi 72 byte.');
                }
            },
        ];
    }
}
