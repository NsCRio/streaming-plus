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

    public static function imdbDataToDatabase(array $imdData) : null|Items {
        if(!empty($imdData) && isset($imdData['id'])){
            $item = Items::query()->whereField('imdb_id', $imdData['id'])->first();
            if(!isset($item))
                $item = new Items();

            $path = self::putImdbDataToLocalStorage($imdData);

            $item->setField('imdb_id', @$imdData['id']);
            $item->setField('category', @$imdData['type']);
            $item->setField('title', @$imdData['title']);
            $item->setField('original_title', @$imdData['originaltitle']);
            $item->setField('year', @$imdData['year']);
            $item->setField('image_url', @$imdData['poster']);
            $item->setField('path', $path);
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
