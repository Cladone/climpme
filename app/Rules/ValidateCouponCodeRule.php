<?php

namespace App\Rules;

use App\Models\Coupon;
use App\Models\Plan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateCouponCodeRule implements ValidationRule
{
    /**
     * The plan id.
     *
     * @var
     */
    private $planId;

    /**
     * Create a new rule instance.
     *
     * @param $planId
     * @return void
     */
    public function __construct($planId)
    {
        $this->planId = $planId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $coupon = Coupon::where('code', '=', $value)->first();

        // If the coupon exists
        if ($coupon) {
            // If the coupon quantity is unlimited, or higher than the number of redeems
            if ($coupon->quantity == -1 || $coupon->quantity > $coupon->redeems) {
                $plan = Plan::where('id', '=', $this->planId)->notDefault()->firstOrFail();

                // If the coupon is not under the selected plan
                if ($plan->coupons && !in_array($coupon->id, $plan->coupons ?? [])) {
                    $fail(__('The coupon code could not be found.'));
                }
            } else {
                $fail(__('The coupon code has expired.'));
            }
        } else {
            $fail(__('The coupon code could not be found.'));
        }
    }
}
