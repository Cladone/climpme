<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateDomainOwnershipRule implements ValidationRule
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
        // If the domain has a user id, and it is not the same with the user's id
        // or if the domain is a global domain, but not the default one, and the user does not have access to global domains
        if (($this->domain->user_id && $this->domain->user_id != $this->user->id) || (!$this->domain->user_id && config('settings.short_domain') && config('settings.short_domain') != $value && $this->user->cannot('globalDomains', ['App\Models\Link']))) {
            $fail(__('The :attribute field is invalid.'));
        }
    }
}
