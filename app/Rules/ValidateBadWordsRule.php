<?php

namespace App\Rules;

Use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateBadWordsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (config('settings.bad_words')) {
            $badWords = preg_split('/\n|\r/', config('settings.bad_words'), -1, PREG_SPLIT_NO_EMPTY);

            foreach($badWords as $word) {
                // Search for the word in string
                if(strpos(mb_strtolower($value), mb_strtolower($word)) !== false) {
                    $fail(__('The :attribute field contains a keyword that is banned.'));
                }
            }
        }
    }
}
