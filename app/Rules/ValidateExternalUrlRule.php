<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateExternalUrlRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure the external URL does not resolve to the app's URL
        if (parse_url($value, PHP_URL_HOST) == parse_url(config('app.url'), PHP_URL_HOST)) {
            $fail(__('The :attribute field is invalid.'));
        }
    }
}
