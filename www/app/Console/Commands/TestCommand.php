<?php

namespace App\Console\Commands;

use App\Models\Jellyfin\ApiKeys;
use App\Models\Jellyfin\Users;
use App\Services\Api\ExternalApi;
use App\Services\Jellyfin\Api\JellyfinApiManager;
use App\Services\Scraper\WebDriver\WebDriverManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command';

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

//        $api = new JellyfinApiManager();
//        $res = $api->getAllApiKeys();
        $res = JellyfinApiManager::call('/Users');

        dd($res);
        //$apikey->save();

        //printf '0\n1992\n0\n' | resources/python/bin/python3 resources/python/run_streaming.py

        $path = "/var/www/resources/python";
        $output = [];
        $return_var = "";
        //exec("printf '0\n1992\n0\n' | resources/python/bin/python3 resources/python/run_streaming.py", $output, $return_var);

        dd($output, $return_var);

        //try {

            dd(WebDriverManager::driverChrome());
            dd(WebDriverManager::driverFirefox());

            $url = "https://streamingcommunity.computer/archivio";
            $api = ExternalApi::call('GET', $url, [
                'accept' => 'text/html, application/xhtml+xml',
            ],
            [
                'search' => 'Interstellar',
                'type' => 'movie',
            ]);

            dd($api);

//        }catch (\Exception $e){
//            dd($e);
//        }

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");
    }
}
