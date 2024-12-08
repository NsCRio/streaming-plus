<?php

namespace App\Console\Commands;

use App\Services\Jellyfin\JellyfinApiManager;
use Illuminate\Console\Command;

class JellyfinSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jellyfin:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to setup Jellyfin for Streaming Plus';

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

        $moviesPath = sp_data_path('library/movies');
        $tvSeriesPath = sp_data_path('library/tvSeries');

        if(!file_exists($moviesPath))
            mkdir($moviesPath, 0777, true);

        if(!file_exists($tvSeriesPath))
            mkdir($tvSeriesPath, 0777, true);

        sleep(30); //Do il tempo a Jellyfin di avviarsi
        $api = new JellyfinApiManager();

        $this->info('####### Update Configuration #######');
        $api->updateConfiguration([
            'ServerName' => "Streaming Plus #".crc32(env('HOSTNAME')),
            'PreferredMetadataLanguage' => env('LANG', 'en'),
            'MetadataCountryCode' => strtoupper(env('LANG', 'en')),
            'UICulture' => strtolower(env('LANG', 'en')),
        ]);
        $api->updateBranding([
            'CustomCss' => "",
            'LoginDisclaimer' => "Welcome to Streaming Plus! This service is open source, we take no responsibility for the use of this software, all misuse is at the user's discretion.",
            //'SplashscreenEnabled' => "'true'"
        ]);

        $this->info('####### Create library folders #######');
        $api->createVirtualFolderIfNotExist("Movies", "movies");
        $api->createVirtualFolderIfNotExist("TV Series", "tvshows");

        sleep(20);
        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        exit(1);
    }
}
