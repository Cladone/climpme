<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateLinkStatsPasswordRule implements ValidationRule
{
    /**
     * @var
     */
    private $link;

    /**
     * Create a new rule instance.
     *
     * @param $link
     * @return void
     */
    public function __construct($link)
    {
        $this->link = $link;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value != $this->link->password) {
            $fail(__('The entered password is not correct.'));
        }
    }
}
