<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;

class ValidateS3StorageCredentialsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        config(['filesystems.disks.' . $value . '.key' => request()->input('storage_key')]);
        config(['filesystems.disks.' . $value . '.secret' => request()->input('storage_secret')]);
        config(['filesystems.disks.' . $value . '.region' => request()->input('storage_region')]);
        config(['filesystems.disks.' . $value . '.bucket' => request()->input('storage_bucket')]);
        config(['filesystems.disks.' . $value . '.endpoint' => (str_starts_with(request()->input('storage_endpoint'), 'https://') ? request()->input('storage_endpoint') : 'https://' .  request()->input('storage_endpoint'))]);

        try {
            Storage::disk($value)->files();
        } catch (\Exception $e) {
            $fail($e->getMessage());
        }
    }
}
