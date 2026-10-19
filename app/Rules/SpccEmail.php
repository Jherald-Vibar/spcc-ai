<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Only SPCC school accounts may register: Surname_name@spcc.edu.ph
 */
class SpccEmail implements ValidationRule
{
    public const DOMAIN = 'spcc.edu.ph';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // "D" makes $ match only at the very end (otherwise a trailing "\n" would slip through)
        $pattern = '/^[a-z0-9._-]+@' . preg_quote(self::DOMAIN, '/') . '$/iD';

        if (! is_string($value) || ! preg_match($pattern, $value)) {
            $fail('Only SPCC accounts are allowed. Use your school email (Surname_name@' . self::DOMAIN . ').');
        }
    }
}
