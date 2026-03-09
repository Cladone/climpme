<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateDnsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If the domain is not the same with the installation URL
        if ($value != parse_url(config('app.url'), PHP_URL_HOST)) {
            $dnsRecords = [];
            try {
                $dnsRecords = dns_get_record($value, DNS_A + DNS_CNAME);
            } catch (\Exception $e) {
                $fail(__($e->getMessage()));
            }

            $isValid = false;
            foreach ($dnsRecords as $record) {
                if ($record['type'] === 'A') {
                    if ($record['ip'] == getHostIp()) {
                        $isValid = true;
                    }
                } elseif ($record['type'] === 'CNAME') {
                    if ($record['target'] == getHostIp()) {
                        $isValid = true;
                    }
                }
            }

            if (!$isValid) {
                $fail(__('The DNS records do not point to our server, or they are not propagated yet, this can take up to 24 hours.'));
            }
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // If the domain is the same with the installation URL
        if ($value == parse_url(config('app.url'))['host']) {
            return true;
        }

        try {
            $dnsRecords = dns_get_record($value, DNS_A + DNS_CNAME);
        } catch (\Exception $e) {
            $dnsRecords = [];
        }

        foreach ($dnsRecords as $record) {
            if ($record['type'] === 'A') {
                if ($record['ip'] == getHostIp()) {
                    return true;
                }
            } elseif ($record['type'] === 'CNAME') {
                if ($record['target'] == getHostIp()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('The DNS records do not point to our server, or they are not propagated yet, this can take up to 24 hours.');
    }
}
