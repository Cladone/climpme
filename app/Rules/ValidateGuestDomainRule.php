<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateGuestDomainRule implements ValidationRule
{
    /**
     * @var
     */
    private $domain;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If there's no default domain set
        if (empty(config('settings.short_domain'))) {
            $fail(__('No default domain.'));
        }

        // If the domain is not the same with the default domain
        if ($value != config('settings.short_domain')) {
            $fail(__('You don\'t have access to this feature.'));
        }
    }
}
