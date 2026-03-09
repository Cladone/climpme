<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

trait UserTrait
{
    /**
     * Store the User.
     *
     * @param Request $request
     * @return User
     */
    protected function userStore(Request $request)
    {
        $user = new User;

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->locale = app()->getLocale();
        $user->timezone = config('settings.timezone');
        $user->api_token = Str::random(64);
        $user->tfa = config('settings.registration_tfa');
        $user->default_domain = config('settings.short_domain');

        $user->save();

        if (!config('settings.registration_verification') || $request->is('admin/*') || $request->is(ltrim(config('services.google.redirect'), '/')) || $request->is(ltrim(config('services.azure.redirect'), '/')) || $request->is(ltrim(config('services.apple.redirect'), '/'))) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    /**
     * Update the User.
     *
     * @param Request $request
     * @param User $user
     * @return User
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function userUpdate(Request $request, User $user)
    {
        $user->name = $request->input('name');
        $user->timezone = $request->input('timezone');
        $user->tfa = $request->boolean('tfa');

        if ($request->has('remove_avatar')) {
            if ($user->avatar) {
                Storage::disk(config('settings.storage_driver'))->delete('users/' . $user->id . '/' . $user->avatar);
            }

            $user->avatar = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk(config('settings.storage_driver'))->delete('users/' . $user->id . '/' . $user->avatar);
            }

            try {
                Image::useImageDriver(config('settings.image_driver'))
                    ->loadFile($request->file('avatar')->getRealPath())
                    ->fit(Fit::Contain, config('settings.user_avatar_size'), config('settings.user_avatar_size'))
                    ->format('jpg')
                    ->quality(80)
                    ->save();

                Storage::disk(config('settings.storage_driver'))->putFile('users/' . $user->id, $request->file('avatar'), 'public');

                $user->avatar = $request->file('avatar')->hashName();
            } catch (\Exception $e) {}
        }

        if ($user->email != $request->input('email')) {
            // If email registration site setting is enabled and the request is not from the Admin Panel
            if (config('settings.registration_verification') && !$request->is('admin/*')) {
                // Send send email validation notification
                $user->newEmail($request->input('email'));
            } else {
                $user->email = $request->input('email');
            }
        }

        if ($request->is('admin/*')) {
            $user->role = $request->input('role');

            // Update the password
            if (!empty($request->input('password'))) {
                $user->password = Hash::make($request->input('password'));
            }

            // Update the email verified status
            if ($request->input('email_verified_at')) {
                $user->markEmailAsVerified();
            } else {
                $user->email_verified_at = null;
            }

            // Update the plan
            if ($request->input('plan_id')) {
                $planEndsAt = null;
                // If the plan ends at is set, and the plan is not the default one
                if ($request->input('plan_ends_at') && $request->input('plan_id') != 1) {
                    $planEndsAt = Carbon::createFromFormat('Y-m-d', $request->input('plan_ends_at'), $user->timezone ?? config('app.timezone'))->tz(config('app.timezone'));
                }

                // If the plan has changed
                // or if the plan end date is indefinitely but the date has changed
                // or if the plan has an end date but the end date has changed
                if ($user->active_plan->id != $request->input('plan_id') || ($user->plan_ends_at == null && $user->plan_ends_at != $planEndsAt) || ($user->plan_ends_at && $planEndsAt && !$user->plan_ends_at->isSameDay($planEndsAt))) {
                    $now = Carbon::now();

                    // If the user previously had a subscription, attempt to cancel it
                    if ($user->plan_subscription_id) {
                        $user->planSubscriptionCancel();
                    }

                    $user->plan_id = $request->input('plan_id');
                    $user->plan_interval = null;
                    $user->plan_currency = null;
                    $user->plan_amount = null;
                    $user->plan_payment_processor = null;
                    $user->plan_subscription_id = null;
                    $user->plan_subscription_status = null;
                    $user->plan_created_at = $now;
                    $user->plan_recurring_at = null;
                    $user->plan_trial_ends_at = $user->plan_trial_ends_at ? $now : null;
                    $user->plan_ends_at = $planEndsAt;
                }
            }
        }

        $user->save();

        return $user;
    }

    /**
     * Update the User authed at date.
     *
     * @param User $user
     * @return void
     */
    protected function userUpdateAuthedAt(User $user)
    {
        $user->authed_at = Carbon::now();
        $user->save();
    }
}