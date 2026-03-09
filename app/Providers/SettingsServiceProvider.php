<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        try {
            $settings = Setting::all()->pluck('value', 'name');

            // Set the app's name
            config(['app.name' => $settings['title']]);

            // Store all the database settings in a config array
            foreach ($settings as $key => $value) {
                config(['settings.' . $key => $value]);
            }

            // Set the app's timezone
            config(['app.timezone' => config('settings.timezone')]);

            // Set the app's default theme
            if (!$request->hasCookie('dark_mode')) {
                config(['settings.dark_mode' => config('settings.theme')]);
            } else {
                // Rewrite the app's theme with the user's preference
                if ($request->cookie('dark_mode') == 1) {
                    config(['settings.dark_mode' => 1]);
                } else {
                    config(['settings.dark_mode' => 0]);
                }
            }

            // Set the app's default mail settings
            config(['mail.default' => config('settings.email_driver')]);
            config(['mail.mailers.smtp.host' => config('settings.email_host')]);
            config(['mail.mailers.smtp.port' => config('settings.email_port')]);
            config(['mail.mailers.smtp.encryption' => config('settings.email_encryption')]);
            config(['mail.mailers.smtp.username' => config('settings.email_username')]);
            config(['mail.mailers.smtp.password' => config('settings.email_password')]);
            config(['mail.from.address' => config('settings.email_address')]);
            config(['mail.from.name' => config('settings.title')]);

            // Set the app's captcha settings
            config(['captcha.driver' => config('settings.captcha_driver')]);
            config(['captcha.sitekey' => config('settings.captcha_site_key')]);
            config(['captcha.secret' => config('settings.captcha_secret_key')]);

            // Set the app's social auth settings
            config(['services.google.client_id' => config('settings.auth_google_client_id')]);
            config(['services.google.client_secret' => config('settings.auth_google_client_secret')]);
            config(['services.azure.client_id' => config('settings.auth_microsoft_client_id')]);
            config(['services.azure.client_secret' => config('settings.auth_microsoft_client_secret')]);
            config(['services.apple.client_id' => config('settings.auth_apple_client_id')]);
            config(['services.apple.client_secret' => config('settings.auth_apple_client_secret')]);

            // Set the app's auth settings
            config(['auth.guards.web.remember' => config('settings.auth_remember_me_duration')]);

            // If the storage driver is not the local public driver
            if (config('settings.storage_driver') !== 'public') {
                // Set the app's storage driver settings
                config(['filesystems.disks.' . config('settings.storage_driver') . '.key' => config('settings.storage_key')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.secret' => config('settings.storage_secret')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.region' => config('settings.storage_region')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.bucket' => config('settings.storage_bucket')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.url' => config('settings.storage_url')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.endpoint' => (str_starts_with(config('settings.storage_endpoint'), 'https://') ? config('settings.storage_endpoint') : 'https://' . config('settings.storage_endpoint'))]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.use_path_style_endpoint' => config('settings.storage_use_path_style_endpoint')]);
                config(['filesystems.disks.' . config('settings.storage_driver') . '.throw' => config('settings.storage_throw')]);
            }

            // Get the available locales
            $locales = [];
            if($handle = opendir(app()->langPath())) {
                while(false !== ($locale = readdir($handle))) {
                    if($locale != '.' && $locale != '..' && pathinfo($locale, PATHINFO_EXTENSION) == 'json') {
                        // Set the default locale
                        if (pathinfo($locale, PATHINFO_FILENAME) == config('settings.locale')) {
                            config(['app.locale' => pathinfo($locale, PATHINFO_FILENAME)]);
                        }

                        $locales[] = pathinfo($locale, PATHINFO_FILENAME);
                    }
                }
                closedir($handle);
            }

            // Store the locales
            config(['app.locales' => array_intersect_key(config('languages'), array_flip($locales))]);
        } catch (\Exception $e) {}
    }
}
