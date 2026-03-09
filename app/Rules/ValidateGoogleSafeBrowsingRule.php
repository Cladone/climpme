<?php

namespace App\Rules;

use Closure;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateGoogleSafeBrowsingRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If a GSB API key is present
        if (config('settings.gsb') && config('settings.gsb_key')) {
            $urls = preg_split('/\n|\r/', $value, -1, PREG_SPLIT_NO_EMPTY);

            $data = [];
            foreach ($urls as $url) {
                // Check if the protocol is http or https
                if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                    $data[] = ['url' => $url];
                }
            }

            // If links are set
            if (!empty($data)) {
                $httpClient = new HttpClient();

                try {
                    $api = $httpClient->request('POST', 'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' . config('settings.gsb_key'), [
                            'headers' => [
                                'Content-Type' => 'application/json'
                            ],
                            'body' => json_encode([
                                'client' => [
                                    'clientId' => mb_strtolower(config('settings.title')),
                                    'clientVersion' => config('info.software.version'),
                                ],
                                'threatInfo' => [
                                    'threatTypes' => [
                                        'MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'
                                    ],
                                    'platformTypes' => [
                                        'ALL_PLATFORMS',
                                    ],
                                    'threatEntryTypes' => [
                                        'URL', 'EXECUTABLE'
                                    ],
                                    'threatEntries' => [
                                        $data
                                    ],
                                ],
                            ])
                        ]
                    );

                    $response = json_decode($api->getBody()->getContents(), true);
                } catch (\Exception $e) {
                    $fail(__($e->getResponse()->getBody()->getContents()));
                }

                // If no threats found
                if (!empty($response)) {
                    $fail(__('This link has been banned.'));
                }
            }
        }
    }
}
