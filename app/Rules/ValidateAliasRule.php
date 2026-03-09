<?php

namespace App\Rules;

use App\Models\Link;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateAliasRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $conditions = [];

        $conditions[] = ['alias', '=', $value];

        // If the query is for a specific link
        if (request()->route('id')) {
            // Exclude the link when validating the alias
            $conditions[] = ['id', '!=', request()->route('id')];

            $link = Link::findOrFail(request()->route('id'));
            $conditions[] = ['domain_id', '=', $link->domain->id ?? null];
        } else {
            // If the request has a link under a domain
            if (request()->input('domain_id')) {
                $conditions[] = ['domain_id', '=', request()->input('domain_id')];
            }
            // Check for links that are not under a domain
            else {
                $conditions[] = ['domain_id', '=', null];
            }
        }

        if (Link::where($conditions)->exists()) {
            $fail(__('validation.unique'));
        }
    }
}
