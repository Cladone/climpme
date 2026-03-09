<?php

namespace App\Rules;

use Closure;
use App\Models\Space;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateSpaceOwnershipRule implements ValidationRule
{
    /**
     * @var
     */
    private $userId;

    /**
     * Create a new rule instance.
     *
     * @param $userId
     * @return void
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!empty($value)) {
            if (!Space::where([['id', '=', $value], ['user_id', '=', $this->userId]])->exists()) {
                $fail(__('The :attribute field is invalid.'));
            }
        }
    }
}
