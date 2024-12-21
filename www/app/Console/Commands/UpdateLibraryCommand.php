<?php

namespace App\Console\Commands;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class UpdateLibraryCommand extends Command
{
    protected $signature = 'library:update';
    protected $description = 'Command to update Streaming Plus Library';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('start.');

        $start = microtime(true);
        set_time_limit(3600);
        ini_set('default_socket_timeout', 10);
        ini_set('memory_limit', '4000M');

        Cache::flush();
        Session::flush();
        Log::info('[Library Update] cache cleared.');

        //Elimino gli items che esistono da più di 5 giorni e non sono mai stati aperti
        $items = Items::query()->whereNull('item_jellyfin_id')
            ->where('created_at', '<=', Carbon::now()->subDays(config('jellyfin.delete_unused_after')))->get();
        $count = $items->count();
        foreach ($items as $item) {
           if(isset($item->item_path))
               $item->removeFromLibrary();
           $item->delete();
        }
        Log::info('[Library Update] '.$count.' unused items removed.');

        //Faccio l'aggiornamento delle serie tv per vedere se ci sono nuovi episodi
        $items = Items::query()->where('item_type','tvSeries')->whereNotNull('item_jellyfin_id')
            ->where('updated_at', '<=', Carbon::now()->subHours(config('jellyfin.update_series_after')))->get();
        $count = $items->count();
        foreach ($items as $item) {
            $imdbData = $item->getImdbData();
            if(!isset($imdbData['enddate'])) { //Solo se non è conclusa
                $item->updateItemToLibrary();
                sleep(1);
            }
        }
        Log::info('[Library Update] '.$count.' tv series updated.');

        //Elimino le stream create da più di tot ore
        Streams::query()->where('updated_at', '<=', Carbon::now()->subHours(config('jellyfin.delete_streams_after')))->delete();
        Log::info('[Library Update] old streams deleted.');


        $api = new JellyfinApiManager();
        //Controllo se funziona ancora l'api key
        if($api->testApiKey()){
            $api->setAuthenticationByApiKey();
            $api->startLibraryScan();
        }else{
            unlink(config('jellyfin.api_key_path'));
        }

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        exit(1);
    }
}
