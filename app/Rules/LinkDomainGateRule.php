<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LinkDomainGateRule implements ValidationRule
{
    /**
     * @var
     */
    private $user;

    /**
     * @var
     */
    private $domain;

    /**
     * Create a new rule instance.
     *
     * @param $user
     * @param $domain
     * @return void
     */
    public function __construct($user, $domain)
    {
        $this->user = $user;
        $this->domain = $domain;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If the domain has a user id, and the user does not have access to custom domains
        // or if the domain is a global domain, but not the default one, and the user does not have access to global domains
        if (
            ($this->domain->user_id && $this->user->cannot('domains', ['App\Models\Link'])) ||
            (!$this->domain->user_id && config('settings.short_domain') && config('settings.short_domain') != $value && $this->user->cannot('globalDomains', ['App\Models\Link']))
        ) {
            $fail(__('You don\'t have access to this feature.'));
        }
    }
}
