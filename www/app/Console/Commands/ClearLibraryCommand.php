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

        //$api = new JellyfinApiManager();

        foreach (config('jellyfin.virtualFolders') as $virtualFolder){
            if(!file_exists($virtualFolder['path']))
                mkdir($virtualFolder['path'], 0777, true);

            system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".$virtualFolder['path']);
            //$api->deleteVirtualFolder($virtualFolder['name']);
        }

        //Artisan::call(JellyfinSetupCommand::class);

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        return;
    }
}
