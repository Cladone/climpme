<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Http\Requests\ValidateLinkRedirectPasswordRequest;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use GeoIp2\Database\Reader as GeoIP;
use Illuminate\Support\Facades\DB;
use WhichBrowser\Parser as UserAgent;

class RedirectController extends Controller
{
    /**
     * Handle the Redirect.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request, $id)
    {
        // Get the local host
        $local = parse_url(config('app.url'))['host'];

        // Get the request host
        $remote = $request->getHost();

        $link = null;

        // Get the remote domain
        $domain = Domain::where('name', '=', $remote)->first();

        // If the domain exists
        if ($domain) {
            // Get the link
            $link = Link::where([['alias', '=', $id], ['domain_id', '=', $domain->id]])->first();
        }

        // If the link exists
        if ($link) {
            // If the user is disabled
            if ($link->user_id != 0 && $link->user->trashed()) {
                return view('redirect.disabled', ['link' => $link]);
            }

            // If the link contains banned words
            $bannedWords = preg_split('/\n|\r/', config('settings.bad_words'), -1, PREG_SPLIT_NO_EMPTY);

            foreach($bannedWords as $word) {
                // Search for the word in string
                if(strpos(mb_strtolower($link->url), mb_strtolower($word)) !== false) {
                    return view('redirect.banned', ['link' => $link]);
                }
            }

            $referrer = parse_url($request->server('HTTP_REFERER'), PHP_URL_HOST) ?? null;

            // If the link is password protected, but no validation has been done
            if ($link->redirect_password && $request->session()->get('verified_link') != $link->id) {
                // Cache the referrer
                $request->session()->put('referrer' . $link->id, $referrer);
            } elseif($link->redirect_password && $request->session()->get('verified_link') == $link->id) {
                // Retrieve the cached referrer
                $referrer = $request->session()->get('referrer' . $link->id);

                // If there's no additional consent required
                if (count($link->pixels) == 0) {
                    // Clear the cached referrer
                    $request->session()->forget('referrer' . $link->id);
                }
            }

            if (array_key_exists(1, $request->segments())) {
                if ($link->redirect_password && $request->session()->get('verified_link') != $link->id) {
                    return view('redirect.password', ['link' => $link]);
                }

                return view('redirect.preview', ['link' => $link]);
            }

            // If the URL is from a Guest User
            if ($link->user_id == 0) {
                // Increase the total click count
                Link::where('id', $link->id)->increment('clicks', 1);

                return redirect()->to($this->urlParamsForward($link->url), 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            }

            $now = Carbon::now();

            // If the link is not in the active period
            if($link->active_period_start_at && $link->active_period_end_at && !$now->isBetween($link->active_period_start_at, $link->active_period_end_at)) {
                // If the link has an expiration url
                if ($link->expiration_url) {
                    return redirect()->to($link->expiration_url, 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
                }

                return view('redirect.expired', ['link' => $link]);
            }

            // If the link clicks limit has been exceeded
            if ($link->clicks_limit && $link->clicks >= $link->clicks_limit) {
                // If the link has an expiration url
                if ($link->expiration_url) {
                    return redirect()->to($link->expiration_url, 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
                }

                return view('redirect.expired', ['link' => $link]);
            }

            // If the link is password protected
            if ($link->redirect_password && $request->session()->get('verified_link') != $link->id) {
                return view('redirect.password', ['link' => $link]);
            }

            // If the link contains sensitive content
            if ($link->sensitive_content) {
                // If the user did not previously visit the sensitive content consent page
                if (! $request->session()->get('sensitive' . $link->id)) {
                    session(['referrer' . $link->id => $referrer]);

                    return view('redirect.sensitive-consent', ['link' => $link]);
                } else {
                    // Retrieve the cached referrer
                    $referrer = $request->session()->get('referrer' . $link->id);

                    // If the link does not have any tracking pixels
                    if (count($link->pixels) == 0) {
                        $request->session()->forget('referrer' . $link->id);
                    }
                }
            }

            // If the link requires tracking consent
            if (count($link->pixels) > 0) {
                // If the user did not previously visit the tracking consent page
                if (! $request->hasCookie('tracking' . $link->id)) {
                    // Cache the referrer
                    session(['referrer' . $link->id => $referrer]);

                    return view('redirect.tracking-consent', ['link' => $link]);
                } else {
                    // Retrieve the cached referrer
                    $referrer = $request->session()->get('referrer' . $link->id);

                    $request->session()->forget('referrer' . $link->id);
                }
            }

            $ua = new UserAgent(getallheaders());

            // If the UA is a BOT
            if ($ua->device->type == 'bot') {
                return redirect()->to($this->urlParamsForward($link->url), 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            }

            // Get the user's geolocation
            try {
                $geoip = (new GeoIP(storage_path('app/geoip/GeoLite2-City.mmdb')))->city($request->ip());

                $continentCode = $geoip->continent->code;
                $countryCode = $geoip->country->isoCode;
                $country = $geoip->country->isoCode . ':' . $geoip->country->name;
                $city = $geoip->country->isoCode . ':' . $geoip->city->name . (isset($geoip->mostSpecificSubdivision->isoCode) ? ', ' . $geoip->mostSpecificSubdivision->isoCode : '');
            } catch (\Exception $e) {
                $continentCode = $countryCode = $country = $city = null;
            }

            // Add the country
            $data['country'] = $country;

            // Add the city
            $data['city'] = $city;

            // Add the browser
            $data['browser'] = $ua->browser->name ?? null;

            // Add the OS
            $data['operating_system'] = $ua->os->name ?? null;

            // Add the device
            $data['device'] = $ua->device->type ?? null;

            // Add the language
            $data['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

            // Add the referrer
            $data['referrer'] = $referrer;

            // Stats
            DB::statement("INSERT INTO `stats` (`link_id`, `referrer`, `operating_system`, `browser`, `device`, `country`, `city`, `language`, `created_at`) VALUES (:link_id, :referrer, :operating_system, :browser, :device, :country, :city, :language, :created_at)", ['link_id' => $link->id, 'referrer' => $data['referrer'] ? mb_substr($data['referrer'], 0, 255) : null, 'operating_system' => $data['operating_system'] ? mb_substr($data['operating_system'], 0, 64) : null, 'browser' => $data['browser'] ? mb_substr($data['browser'], 0, 64) : null, 'device' => $data['device'] ? mb_substr($data['device'], 0, 64) : null, 'country' => $data['country'] ? mb_substr($data['country'], 0, 64) : null, 'city' => $data['city'] ? mb_substr($data['city'], 0, 128) : null, 'language' => $data['language'] ? mb_substr($data['language'], 0, 2) : null, 'created_at' => $now]);

            // Increase the total click count
            Link::where('id', $link->id)->increment('clicks', 1);

            // The default URL to redirect to
            $url = $link->url;

            // If the target type is continents
            if ($link->targets_type == 'continents' && $link->targets !== null) {
                // Redirect the user based on his continent
                if ($link->targets) {
                    foreach ($link->targets as $continent) {
                        if ($continentCode == $continent->key) {
                            $url = $continent->value;
                        }
                    }
                }
            }

            // If the target type is countries
            if ($link->targets_type == 'countries' && $link->targets !== null) {
                // Redirect the user based on his country
                if ($link->targets) {
                    foreach ($link->targets as $country) {
                        if ($countryCode == $country->key) {
                            $url = $country->value;
                        }
                    }
                }
            }

            // If the target type is operating systems
            if ($link->targets_type == 'operating_systems' && $link->targets !== null) {
                // Redirect the user based on the operating system he is on
                if ($link->targets) {
                    foreach ($link->targets as $operatingSystem) {
                        if ($data['operating_system'] == $operatingSystem->key) {
                            $url = $operatingSystem->value;
                        }
                    }
                }
            }

            // If the target type is browsers
            if ($link->targets_type == 'browsers' && $link->targets !== null) {
                // Redirect the user based on the browser he is on
                if ($link->targets) {
                    foreach ($link->targets as $browser) {
                        if ($data['browser'] == $browser->key) {
                            $url = $browser->value;
                        }
                    }
                }
            }

            // If the target type is languages
            if ($link->targets_type == 'languages' && $link->targets !== null) {
                // Redirect the user based on the language he is on
                if ($link->targets) {
                    foreach ($link->targets as $language) {
                        if ($data['language'] == $language->key) {
                            $url = $language->value;
                        }
                    }
                }
            }

            // If the target type is devices
            if ($link->targets_type == 'devices' && $link->targets !== null) {
                // Redirect the user based on the device he is on
                if ($link->targets) {
                    foreach ($link->targets as $device) {
                        if ($data['device'] == $device->key) {
                            $url = $device->value;
                        }
                    }
                }
            }

            // If rotation targeting is enabled
            if ($link->targets_type == 'rotations' && $link->targets !== null) {
                $totalRotations = count($link->targets);

                $last_rotation = 0;
                // If there are links in the rotation
                // And the total available links is higher than the last rotation id
                if ($totalRotations > 0 && $totalRotations > $link->last_rotation) {
                    // Increase the last id
                    $last_rotation = $link->last_rotation + 1;
                }

                // Update the last rotation id
                Link::where('id', $link->id)->update(['last_rotation' => $last_rotation]);
            }

            // If the target type is rotations
            if ($link->targets_type == 'rotations' && $link->targets !== null) {
                if (isset($link->target[$link->last_rotation])) {
                    $url = $link->target[$link->last_rotation]->value;
                }
            }

            // If the link has pixel tracking
            if (count($link->pixels) > 0) {
                // If the user approved tracking consent
                if($request->cookie('tracking' . $link->id) == 1) {
                    return view('redirect.redirect', ['link' => $link, 'url' => $url]);
                }
            }

            return redirect()->to($this->urlParamsForward($url), 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        // If the request comes from a remote source
        if ($local != $remote) {
            // Get the remote domain
            $domain = Domain::where('name', '=', $remote)->first();

            // If the domain exists
            if ($domain) {
                // If the domain has a 404 page defined
                if ($domain->not_found_page) {
                    return redirect()->to($domain->not_found_page, 301)->header('Cache-Control', 'no-store, no-cache, must-revalidate');
                }
            }
        }

        abort(404);
    }

    /**
     * Validate the link's redirect password.
     *
     * @param ValidateLinkRedirectPasswordRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validateRedirectPassword(ValidateLinkRedirectPasswordRequest $request, $id)
    {
        session()->flash('verified_link', $id);
        return redirect()->back();
    }

    /**
     * Validate the link's tracking consent page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validateTrackingConsent(Request $request, $id)
    {
        return redirect()->back()->withCookie('tracking' . $id, $request->input('tracking') ? 1 : 0, (60 * 24 * 30))->with(['sensitive' . $id => 1, 'verified_link' => $id]);
    }

    /**
     * Validate the link's sensitive consent page.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validateSensitiveConsent(Request $request, $id)
    {
        session()->flash('sensitive' . $id, $request->input('sensitive') ? 1 : 0);
        return redirect()->back()->with(['verified_link' => $id]);
    }

    /**
     * Format a URL to append additional parameters.
     *
     * @param $url
     * @return string
     */
    private function urlParamsForward($url)
    {
        $forwardParams = request()->all();

        // If additional parameters are present
        if ($forwardParams) {
            $urlParts = parse_url($url);

            // Explode the original parameters
            parse_str($urlParts['query'] ?? '', $originalParams);

            // Override and merge the original parameters with the new ones
            $parsedParams = array_merge($originalParams, $forwardParams);

            // Build the URL
            $url = $urlParts['scheme'] . '://' . $urlParts['host'] . ($urlParts['path'] ?? '/') . '?' . http_build_query($parsedParams);

            return $url;
        }

        return $url;
    }
}