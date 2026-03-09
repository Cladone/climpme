<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidatePixelValueRule implements ValidationRule
{
    /**
     * The pixel type
     *
     * @var
     */
    private $type;

    /**
     * The expected value format
     *
     * @var
     */
    private $format;

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
        if ($this->type == 'adroll') {
            if (mb_strpos($value, '-') == false) {
                $this->format = 'ADVID-PIXID';
                $fail(__('The :attribute must be in :format format.', ['format' => $this->format]));
            }
        }
    }
}
