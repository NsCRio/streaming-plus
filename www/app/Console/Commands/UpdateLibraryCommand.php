<?php

namespace App\Console\Commands;

use App\Models\Items;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateLibraryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update Streaming Plus Library';

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

        //Elimino gli items che esistono da più di 5 giorni e non sono mai stati aperti
        $items = Items::query()->whereNull('item_jellyfin_id')
            ->where('created_at', '<=', Carbon::now()->subDays(config('jellyfin.delete_unused_after')))->get();
        foreach ($items as $item) {
           if(isset($item->item_path))
               $item->removeFromLibrary();
           $item->delete();
        }

        //Faccio l'aggiornamento delle serie tv per vedere se ci sono nuovi episodi
        $items = Items::query()->where('item_type','tvSeries')
            ->whereNotNull('item_jellyfin_id')
            ->where('updated_at', '<=', Carbon::now()->subHours(config('jellyfin.update_series_after')))->get();
        foreach ($items as $item) {
            $imdbData = $item->getImdbData();
            if(!isset($imdbData['enddate'])) //Solo se non è conclusa
                $item->updateItemToLibrary();
            sleep(1);
        }

        //Avvio la scansione di jellyfin
        $api = new JellyfinApiManager();
        $api->startLibraryScan();

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");

        exit(1);
    }
}
