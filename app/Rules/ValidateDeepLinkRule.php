<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateDeepLinkRule implements ValidationRule
{
    /**
     * @var
     */
    private $user;

    /**
     * Create a new rule instance.
     *
     * @param $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (parse_url($value, PHP_URL_SCHEME) && !in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'])) {
            if (!$this->user || $this->user->cannot('deepLinks', ['App\Models\Link'])) {
                $fail(__('You don\'t have access to this feature.'));
            }
        }
    }
}
