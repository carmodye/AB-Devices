<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\FetchDeviceData::class,
        \App\Console\Commands\FetchDeviceDetails::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('devices:fetch')->everyFiveMinutes();
        $schedule->command('devices:fetch-details')->everyOddHour();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}