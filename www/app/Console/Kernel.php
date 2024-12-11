<?php

namespace App\Console;

use App\Console\Commands\ClearLibraryCommand;
use App\Console\Commands\IMDbDatasetRetriever;
use App\Console\Commands\InitCommand;
use App\Console\Commands\JellyfinSetupCommand;
use App\Console\Commands\TestCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        IMDbDatasetRetriever::class,
        TestCommand::class,

        //Jellyfin Commands
        ClearLibraryCommand::class,
        JellyfinSetupCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command(JellyfinSetupCommand::class)->everyFiveMinutes();
        //$schedule->command('cache:clear')->everyTwoHours();
    }
}
