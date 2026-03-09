<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateUrlsCountRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (count(preg_split('/\n|\r/', $value, -1, PREG_SPLIT_NO_EMPTY)) > config('settings.short_max_multi_links')) {
            $fail(__('The number of links must not exceed :value.', ['value' => config('settings.short_max_multi_links')]));
        }
    }
}
