<?php

namespace App\Rules;

use App\Models\Pixel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

class ValidatePixelOwnershipRule implements ValidationRule
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var
     */
    private $userId;

    /**
     * Create a new rule instance.
     *
     * @param Request $request
     * @param $userId
     * @return void
     */
    public function __construct(Request $request, $userId)
    {
        $this->request = $request;
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail(__('The :attribute field is invalid.'));
        }

        if (is_array($value) && !empty(array_filter($value))) {
            // Get any of user's existing pixels
            if (Pixel::where('user_id', '=', $this->userId)->whereIn('id', array_filter($value))->exists()) {
                // Store the user's pixels
                $this->request->merge(['pixel_ids' => Pixel::where('user_id', '=', $this->userId)->whereIn('id', array_filter($value))->get()->pluck('id')->toArray()]);
            } else {
                $fail(__('The :attribute field is invalid.'));
            }
        }
    }
}
