<?php

namespace App\Rules;

use App\AI\Providers\ProviderGenerationOptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ProviderOptionsJson implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('額外參數必須是 JSON 字串。');

            return;
        }

        try {
            ProviderGenerationOptions::parseJson($value);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
