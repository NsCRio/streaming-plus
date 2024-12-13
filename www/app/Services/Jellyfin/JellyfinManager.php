<?php

namespace App\Services\Jellyfin;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Items\ItemsSearchManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use LaLit\Array2XML;

class JellyfinManager
{
    public static $typesMap = ['Movie' => 'movie', 'Series' => 'tvSeries'];

    /**
     * @throws \Exception
     */
    public static function getItemsFromSearchTerm($searchTerm, $itemType = null, $userId = null, array $query = []) : null|array {
        $api = new JellyfinApiManager();
        $response = $api->getItems($query);
        if(isset($userId))
            $response = $api->getUsersItems($userId, $query);
        $isMissing = (bool) @$query['isMissing'];
        $mediaTypes = (bool) @$query['mediaTypes'];

        if((empty($itemType) || in_array($itemType, ["Movie", "Series"])) && !$isMissing && !$mediaTypes) {
            $search = new ItemsSearchManager($searchTerm, @self::$typesMap[$itemType]);
            $results = $search->search()->getResults();

            if(!empty($results)){
                $jellyfinIds = [];
                if (!empty($response['Items']))
                    $jellyfinIds = array_filter(array_map(function ($item) { return @$item['Id'] ?? null;}, $response['Items']));

                foreach ($results as $result) {
                    if ((isset($result->item_jellyfin_id) && in_array($result->item_jellyfin_id, $jellyfinIds)))
                        continue;

                    $resultType = isset($query['searchTerm']) ? "CollectionFolder" : "Video";
                    $response['Items'][] = $result->getJellyfinListItem($resultType);
                }
            }
            $response['TotalRecordCount'] = count(@$response['Items'] ?? []);
        }
        return $response;
    }


    public static function getItemById(string $itemId, array $query = null): null|array {
        return Cache::remember('jellyfin_item_'.md5($itemId.json_encode($query)), Carbon::now()->addSeconds(10), function () use ($itemId, $query) {
            $outcome = [];
            $api = new JellyfinApiManager();

            //Check on Jellyfin
            if (!empty($query)) {
                $response = $api->getItemFromQuery($itemId, $query);
                if(!empty($response)){
                    $outcome = $response;
                    $imdbId = @$outcome['ProviderIds']['Imdb'];
                    if (isset($imdbId)) {
                        $item = Items::query()->where('item_imdb_id', $imdbId)->first();
                        if (isset($item)) {
                            $item->item_jellyfin_id = $itemId;
                            $item->item_tmdb_id = @$outcome['ProviderIds']['Tmdb'];
                            $item->save();
                        }
                    }
                }
            }

            //Check on Items
            if (empty($outcome)) {
                $item = Items::where('item_md5', $itemId)->first();
                if(isset($item))
                    $outcome = $item->getJellyfinDetailItem();
            }

            //Adds Streams Url
            if(!empty($outcome)) {
                $api = new AddonsApiManager();
                $mediaSources = $api->searchStreamByItemId($itemId);
                $outcome['MediaSources'] = $mediaSources;
            }

            return $outcome;
        });
    }


    public static function getStreamsByItemId(string $itemId, string $mediaSourceId = null): array {
        $api = new JellyfinApiManager();
        $response = $api->getItemPlaybackInfo($itemId);

        $api = new AddonsApiManager();
        $response['MediaSources'] = $api->searchStreamByItemId($itemId, $mediaSourceId);
        if(!empty($response['MediaSources'])) {
            $response['MediaSources'][0]['Id'] = $itemId;
            //$response['MediaSources'][0]['Path'] = get_last_url(sp_url('/stream?streamId='.$mediaSourceId));
        }

        return $response ?? [];
    }

    /**
     * @throws \Exception
     */
    public static function createStructure(string $directory, array $imdbData){
        self::createNfoFile($directory, $imdbData);
        if(!empty($imdbData['seasons'])){
            self::createSeasonsStructure($directory, $imdbData);
        }else{
            self::createStrmFile("", $directory, $imdbData);
        }
    }


    /**
     * @throws \Exception
     */
    protected static function createSeasonsStructure(string $directory, array $imdbData){
        foreach($imdbData['seasons'] as $season => $episodes) {
            $seasonPath = $directory."/Season ".sprintf("%02d", $season);

            if(!file_exists($seasonPath))
                mkdir($seasonPath, 0777, true);

            foreach($episodes as $episode){
                self::createNfoFile($seasonPath, $episode);
                self::createStrmFile("", $seasonPath, $episode);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected static function createNfoFile(string $directory, array $imdbData): ?string {
        $typeMap = ['movie' => 'movie', 'tvSeries' => 'tvshow', 'tvEpisode' => 'episodedetails'];
        if(in_array($imdbData['type'], array_keys($typeMap))){
            try {
                $type = $typeMap[$imdbData['type']];
                $filePath = $directory . "/" . $type . ".nfo";
                if($type == "episodedetails") {
                    $fileName = 'Episode S'.sprintf("%02d", $imdbData['season']).'E'.sprintf("%02d", $imdbData['episode']);
                    $filePath = $directory . "/" . $fileName . ".nfo";
                }

                if (!file_exists($directory))
                    mkdir($directory, 0777, true);

                unset($imdbData['id']);
                unset($imdbData['seasons']);
                unset($imdbData['totalSeasons']);
                unset($imdbData['totalEpisodes']);
                $imdbData['lockdata'] = "false";

                if($imdbData['type'] !== "tvEpisode") {
                    $imagePath = $directory . "/folder.jpeg";
                    if (!file_exists($imagePath)) {
                        try {
                            save_image(@$imdbData['poster'], $imagePath);
                        } catch (\Exception $e) {
                        }
                    }
                    if (file_exists($imagePath))
                        $imdbData['art']['poster'] = $imagePath;
                }

                if (file_exists($filePath)) {
                    $xml = simplexml_load_string(file_get_contents($filePath), "SimpleXMLElement", LIBXML_NOCDATA);
                    $imdbData = array_merge($imdbData, json_decode(json_encode($xml), true));
                }

                $xml = Array2XML::createXML($type, $imdbData);
                file_put_contents($filePath, $xml->saveXML());

                return $filePath;
            }catch (\Exception $e){}
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    public static function createStrmFile(string $streamName = "", string $directory, array $imdbData): ?string {
        try {
            $filePath = $directory . "/" . @$imdbData['imdb_id'];
            $filePath .= (!empty($streamName) ? '-' . $streamName : "") . ".strm";
            $streamUrl = config('app.url').'/stream?';
            $fileContent = $streamUrl.http_build_query([
                'imdbId' => @$imdbData['imdb_id'],
                //'provider' => (!empty($streamName) ? md5($streamName) : "")
            ]);

            if ($imdbData['type'] == "tvEpisode") {
                $fileName = 'Episode S' . sprintf("%02d", $imdbData['season']) . 'E' . sprintf("%02d", $imdbData['episode']);
                $filePath = $directory . "/" . $fileName;
                $filePath .= (!empty($streamName) ? '-' . $streamName : "") . ".strm";

                $fileContent = $streamUrl.http_build_query([
                    'imdbId' => @$imdbData['parent_imdb_id'],
                    'season' => $imdbData['season'],
                    'episode' => $imdbData['episode'],
                    //'provider' => (!empty($streamName) ? md5($streamName) : "")
                ]);
            }

            if (!file_exists($filePath))
                file_put_contents($filePath, $fileContent);

            return $filePath;
        }catch (\Exception $e){}
        return null;
    }

}
