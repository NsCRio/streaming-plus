<?php

namespace App\Console\Commands;

use App\Models\Movies;
use App\Models\TvSeries;
use App\Models\TvSeriesEpisodes;
use App\Services\Helpers\FileHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use function Symfony\Component\String\s;

class IMDbDatasetRetriever extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imdb:dataset-retriever';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve data from IMDb';

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

        try {
            //Titles import
            $this->info('######### Titles Import ##########');
            $titles = static::getTitles();
            foreach ($titles as $title) {
                if(!empty(trim($title['tconst']))) {
                    $this->info('- ' . $title['primaryTitle']);
                    $class = null;
                    switch ($title['titleType']) {
                        case "movie":
                            $class = Movies::class;
                            break;
                        case "tvseries":
                            $class = TvSeries::class;
                            break;
                        case "tvepisode":
                            $class = TvSeriesEpisodes::class;
                            break;
                        default:
                            break;
                    }
                    if (isset($class)) {
                        $entity = $class::whereField('imdb_id', $title['tconst'])->first();
                        if (!$entity) {
                            $entity = new $class;
                            $entity->setField('imdb_id', $title['tconst']);
                            $entity->setField('title', $title['primaryTitle']);
                            $entity->setField('original_title', $title['originalTitle']);
                            $entity->setField('year', (int)$title['startYear']);
                            $entity->setField('duration_min', (int)$title['runtimeMinutes']);
                            $entity->setField('categories', (int)$title['genres']);
                            $entity->save();
                        }
                    }
                }
            }

            //Episodes
            $this->info('######### Episodes Import ##########');
            $episodes = static::getEpisodes();
            foreach ($episodes as $ep) {
                if(!empty(trim($ep['tconst'])) && !empty(trim($ep['parentTconst']))) {
                    $episode = TvSeriesEpisodes::whereField('imdb_id', $ep['tconst'])->first();
                    $series = TvSeries::whereField('imdb_id', $ep['parentTconst'])->first();
                    if($episode && $series) {
                        $this->info('- '.$episode->getField('title'));
                        $episode->setField('series_id', $series->id);
                        $episode->save();
                    }
                }
            }

        }catch (\Exception $e){
            dd($e);
        }

        $this->info("end. (".number_format(microtime(true) - $start, 2)."s)\n");
    }

    /*
     * Non-commercial Dataset from Imdb
     * Guide https://developer.imdb.com/non-commercial-datasets/#titleepisodetsvgz
     */
    private static $datasetUrl = "https://datasets.imdbws.com";
    private static $datasetStoragePath = "temp/datasets/imdb";


    private static function getTitles()
    {
        $items = [];
        $header = [];
        $count = 0;
        $files = static::getDatasetFile("title.basics.tsv.gz");
        foreach ($files as $key => $file) {
            $data = FileHelper::parseDelimitedFile($file, "\t", false);
            if($key == 0 && isset($data[0])){
                $header = $data[0];
                unset($data[0]);
            }
            foreach ($data as $item){
                if(count($header) !== count($item))
                    continue;

                $item = array_combine($header, $item);
                //Filters
                if(!in_array($item['titleType'], ['movie', 'tvseries', 'tvepisode']))
                    continue;

                if((int) $item['startYear'] < 2000)
                    continue;

                $items[] = $item;
            }
            $count += count($items);
        }
        //dd($count);
        return $items;
    }

    private static function getEpisodes()
    {
        $items = [];
        $header = [];
        $count = 0;
        $files = static::getDatasetFile("title.episode.tsv.gz");
        foreach ($files as $key => $file) {
            $data = FileHelper::parseDelimitedFile($file, "\t", false);
            if($key == 0 && isset($data[0])){
                $header = $data[0];
                unset($data[0]);
            }
            foreach ($data as $item){
                if(count($header) !== count($item))
                    continue;

                $item = array_combine($header, $item);
                $items[] = $item;
            }
            $count += count($items);
        }
        return $items;
    }

    private static function getDatasetFile($fileName)
    {
        $filePath = sp_data_path(static::$datasetStoragePath)."/".$fileName;
        $fileInfo = pathinfo($filePath);
        if(!file_exists($filePath) || (file_exists($filePath) && Carbon::parse(filemtime($filePath))->addHour()->isBefore(Carbon::now()->subHour()))){
            $url = self::$datasetUrl."/".$fileName;
            file_put_contents($filePath, fopen($url, 'r'));
        }

        $uncopressedFolder = $fileInfo['dirname']."/uncompressed";
        if(!file_exists($uncopressedFolder))
            mkdir($uncopressedFolder);

        $uncopressedFilePath = $uncopressedFolder."/".str_replace('.gz', '', $fileName);
        if(!file_exists($uncopressedFilePath) || (file_exists($uncopressedFilePath) && Carbon::parse(filemtime($uncopressedFilePath))->addHour()->isBefore(Carbon::now()->subHour()))){
            $uncopressedFilePath = FileHelper::extractGZ($filePath, $uncopressedFilePath);
        }
        return FileHelper::splitFile($uncopressedFilePath, '51200k'); //50mb
    }

}
