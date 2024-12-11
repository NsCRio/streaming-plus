<?php

namespace App\Console\Commands;

use App\Services\Jellyfin\JellyfinApiManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearLibraryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to clear Streaming Plus Library';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('start.');

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        Artisan::call('cache:clear');
        Artisan::call('migrate:fresh');

        system("rm -rf ".escapeshellarg(sp_data_path('library')), $result);

        $moviesPath = sp_data_path('library/movies');
        $tvSeriesPath = sp_data_path('library/tvSeries');

        if(!file_exists($moviesPath))
            mkdir($moviesPath, 0777, true);

        if(!file_exists($tvSeriesPath))
            mkdir($tvSeriesPath, 0777, true);

        $api = new JellyfinApiManager();
        $api->removeVirtualFolder("Movies");
        $api->removeVirtualFolder("TV Series");

        Artisan::call(JellyfinSetupCommand::class);

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        return;
    }
}
