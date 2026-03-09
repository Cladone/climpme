<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateTargetKeyRule implements ValidationRule
{
    /**
     * @var
     */
    var $type;

    /**
     * Create a new rule instance.
     *
     * @param $type
     * @return void
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, array_keys(config('continents'))) && $this->type == 'continents') {
            $fail(__('The :attribute field is invalid.'));
        }

        if (!in_array($value, array_keys(config('countries'))) && $this->type == 'countries') {
            $fail(__('The :attribute field is invalid.'));
        }

        if (!in_array($value, config('operating_systems')) && $this->type == 'operating_systems') {
            $fail(__('The :attribute field is invalid.'));
        }

        if (!in_array($value, config('browsers')) && $this->type == 'browsers') {
            $fail(__('The :attribute field is invalid.'));
        }

        if (!in_array($value, array_keys(config('languages'))) && $this->type == 'languages') {
            $fail(__('The :attribute field is invalid.'));
        }

        if (!in_array($value, array_keys(config('devices'))) && $this->type == 'devices') {
            $fail(__('The :attribute field is invalid.'));
        }
    }
}
