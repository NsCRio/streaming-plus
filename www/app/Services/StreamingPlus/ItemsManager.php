<?php

namespace App\Services\StreamingPlus;

use App\Models\Items;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ItemsManager
{
    protected static $libraryPath = 'library';

    protected $model;
    public function __construct(Items $model){
        $this->model = $model;
    }

    public static function imdbDataToDatabase(array $imdbData) : null|Items {
        if(!empty($imdbData) && isset($imdbData['id'])){
            $item = Items::query()->where('item_imdb_id', $imdbData['id'])->first();
            if(!isset($item)) {
                $item = new Items();
                $item->item_imdb_id = @$imdbData['id'];
                $item->item_category = @$imdbData['type'];
                $item->item_title = @$imdbData['title'];
                $item->item_original_title = @$imdbData['originaltitle'];
                $item->item_year = @$imdbData['year'];
                $item->item_image_url = @$imdbData['poster'];
            }
            $item->item_path = self::putImdbDataToLocalStorage($imdbData);
            $item->save();
            return $item;
        }
        return null;
    }

    public static function getImdbDataFromLocalStorage($imdbId, $imdbType){
        $directory = sp_data_path(self::$libraryPath.'/'.Str::plural($imdbType).'/'.$imdbId);
        $file = $directory.'/'.$imdbId. '.json';
        if(file_exists($file) && !Carbon::parse(filemtime($file))->isBefore(Carbon::now()->subDays(2))) {
            return json_decode(file_get_contents($file), true);
        }
        return [];
    }

    /**
     * @throws \Exception
     */
    public static function putImdbDataToLocalStorage(array $imdbData): ?string {
        if(!empty($imdbData) && isset($imdbData['id'])) {
            $path = self::$libraryPath.'/'.Str::plural($imdbData['type']).'/'.$imdbData['id'];
            $directory = sp_data_path($path);
            $file = '/' . $imdbData['id'] . '.json';

            if (!file_exists($directory))
                mkdir($directory, 0777, true);

            JellyfinManager::createStructure($directory, $imdbData);

            file_put_contents($directory . $file, json_encode($imdbData, JSON_PRETTY_PRINT));
            return $path;
        }
        return null;
    }
}
