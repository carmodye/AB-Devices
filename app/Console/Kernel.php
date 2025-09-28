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
        \App\Console\Commands\FetchDeviceData::class,
        \App\Console\Commands\FetchDeviceDetails::class,
        // Add other custom commands here if you have any
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule FetchDeviceData to run every minute for all clients
        $schedule->command('devices:fetch')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/devices-fetch.log'));

        // Schedule FetchDeviceDetails to run every minute for all clients
        $schedule->command('device-details:fetch')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/device-details-fetch.log'));

        // Add other scheduled tasks here if needed
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
