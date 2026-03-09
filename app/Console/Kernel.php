<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\DeleteUnverifiedUsersCommand',
        \App\Console\Commands\AggregateTrafficHourly::class, // 👈 Register traffic aggregation
        \App\Console\Commands\BackfillCountryFromGeoip::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Existing jobs
        $schedule->command('cache:clear')->weeklyOn(0, '01:00');
        $schedule->command('view:clear')->weeklyOn(0, '02:00');
        $schedule->command('auth:clear-resets')->weeklyOn(0, '03:00');
        $schedule->command('cron:delete-unverified-users')->dailyAt('04:00');

        // 👇 New: aggregate traffic data every 10 minutes (lookback 120 min to catch late events)
        $schedule->command('traffic:aggregate-hourly --window=120')->everyTenMinutes();
        $schedule->command('traffic:rollup --since=2h')->everyFiveMinutes();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
