<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) < 8) {
            $fail(__('La contraseña debe tener al menos 8 caracteres.'));

            return;
        }

        if (! preg_match('/[a-z]/u', $value)) {
            $fail(__('La contraseña debe incluir al menos una letra minúscula.'));
        }

        if (! preg_match('/[A-Z]/u', $value)) {
            $fail(__('La contraseña debe incluir al menos una letra mayúscula.'));
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail(__('La contraseña debe incluir al menos un número.'));
        }

        if (! preg_match('/[^\p{L}0-9]/u', $value)) {
            $fail(__('La contraseña debe incluir al menos un símbolo.'));
        }
    }
}
