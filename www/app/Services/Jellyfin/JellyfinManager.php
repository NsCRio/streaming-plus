<?php

namespace App\Services\Jellyfin;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Items\ItemsSearchManager;
use App\Services\Streams\StreamsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use LaLit\Array2XML;

class JellyfinManager
{
    public static $typesMap = ['Movie' => 'movie', 'Series' => 'tvSeries'];

    public static function encodeItemId(array $data){
        return base64_encode(json_encode($data));
    }

    public static function decodeItemId(string $itemId){
        if (strlen($itemId) > 32) { //default is md5
            $outcome = json_decode(base64_decode($itemId) ,true);
            if(!isset($outcome['mediaSourceId']))
                $outcome['mediaSourceId'] = @$outcome['streamId'];
            return $outcome;
        }
        return ['itemId' => $itemId, 'mediaSourceId' => $itemId];
    }

    /**
     * @param string $itemId
     * @param array $query
     * @return array
     */

    public static function getItemDetailById(string $itemId, array $query = []): array {
        $api = new JellyfinApiManager();
        if(!empty($query)){
            $detail = $api->getItemFromQuery($itemId, $query);
        }else{
            $detail = $api->getItem($itemId);
        }
        if(isset($detail)) {
            $imdbId = @$detail['ProviderIds']['Imdb'];
            $tmdbId = @$detail['ProviderIds']['Tmdb'];
            if (isset($detail['SeriesId']) && isset($detail['SeasonId'])) {
                $query = $api->getItemFromQuery($detail['SeriesId']);
                if (!empty($query)) {
                    $imdbId = @$query['ProviderIds']['Imdb'] . ':' . $detail['ParentIndexNumber'] . ':' . $detail['IndexNumber'];
                    $tmdbId = @$query['ProviderIds']['Tmdb'] . ':' . $detail['ParentIndexNumber'] . ':' . $detail['IndexNumber'];
                }
            }
            $detail['imdbId'] = $imdbId;
            $detail['tmdbId'] = $tmdbId;
        }
        return $detail ?? [];
    }

    /**
     * @param string $type
     * @param int $limit
     * @return array
     */

    public static function getDashboardTopItems(string $type = "movie", array $query = [], int $limit = 10): array {
        //return Cache::remember('jellyfin_dashboard_items_'.md5($type.$limit), Carbon::now()->addHours(2), function () use ($type, $limit) {
            $api = new AddonsApiManager(config('cinemeta.url'));
            $catalog = $api->getCatalog($type, 'top');
            if($limit > 0)
                $catalog = array_values(array_slice($catalog, 0, $limit));
            $outcome = [];
            foreach($catalog as $outcomeItem){
                $item = Items::query()->where('item_imdb_id', $outcomeItem['imdb_id'])->first();
                if(!isset($item)) {
                    $api = new JellyfinApiManager();
                    $system = $api->getSystemInfo();

                    $typeMap = ['movie' => 'movie', 'series' => 'tvSeries'];
                    $item = new Items();
                    $item->item_md5 = @md5(@$outcomeItem['imdb_id']);
                    $item->item_imdb_id = @$outcomeItem['imdb_id'];
                    $item->item_tmdb_id = @$outcomeItem['moviedb_id'];
                    $item->item_type = @$typeMap[@$outcomeItem['type']];
                    $item->item_title = @$outcomeItem['name'];
                    $item->item_original_title = @$outcomeItem['name'];
                    $item->item_year = @$outcomeItem['year'];
                    $item->item_image_url = @$outcomeItem['poster'];
                    $item->item_image_md5 = @md5(@$outcomeItem['poster']);
                    $item->item_server_id = @$system['Id'];
                    $item->save();
                }
                $resultType = !isset($query['MediaTypes']) ? "CollectionFolder" : "Video";
                $outcome[$item->item_md5] = $item->getJellyfinListItem($resultType);
            }
            return array_values($outcome);
        //});
    }

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


    public static function getItemById(string $itemId, array $query = []): null|array {
        $itemData = self::decodeItemId($itemId);

        //Is an item from search view that is not yet in the Library
        $item = Items::where('item_md5', $itemData['itemId'])->first();
        if(isset($item))
            return $item->getJellyfinDetailItem();

        //Search from Jellyfin items on Library
        $outcome = self::getItemDetailById($itemData['itemId'], $query);
        if(!empty($outcome)){
            //Adds Streams url to Item
            $outcome['MediaSources'] = StreamsManager::searchStreamsByItemId($itemData['itemId'], @$itemData['streamId']);
            //Save some useful information for DB
            if (isset($outcome['imdbId'])) {
                $item = Items::query()->where('item_imdb_id', $outcome['imdbId'])->first();
                if (isset($item)) {
                    $item->item_jellyfin_id = $itemData['itemId'];
                    $item->item_tmdb_id = $outcome['tmdbId'];
                    $item->save();
                }
            }
        }

        return $outcome;
    }


    public static function getStreamsByItemId(string $itemId, string $mediaSourceId = null): array {
        $api = new JellyfinApiManager();
        $response = $api->getItemPlaybackInfo($itemId);
        $response['MediaSources'] = StreamsManager::searchStreamsByItemId($itemId, $mediaSourceId);
        return $response ?? [];
    }

    public static function getStreamByItemId(string $itemId, string $mediaSourceId): null|array {
        $streams = self::getStreamsByItemId($itemId, $mediaSourceId);
        if(!empty($streams) && !empty($streams['MediaSources']))
            return $streams['MediaSources'][array_key_first($streams['MediaSources'])];
        return null;
    }


    public static function saveApiKey($apiKey){
        $path = config('jellyfin.api_key_path');
        file_put_contents($path, $apiKey);
        return $path;
    }

    public static function getApiKey(){
        $path = config('jellyfin.api_key_path');
        return @file_get_contents($path) ?? null;
    }


    /**
     * @throws \Exception
     */
    public static function createStructure(string $directory, array $imdbData): void {
        self::createNfoFile($directory, $imdbData);
        if(!empty($imdbData['seasons'])){
            self::createSeasonsStructure($directory, $imdbData);
        }else{
            self::createStrmFile($directory, $imdbData);
        }
    }


    /**
     * @throws \Exception
     */
    protected static function createSeasonsStructure(string $directory, array $imdbData): void {
        foreach($imdbData['seasons'] as $season => $episodes) {
            $seasonPath = $directory."/".$imdbData['imdb_id'].":".$season;
            $season = [
                'type' => 'tvSeason',
                'parent_imdb_id' => $imdbData['imdb_id'],
                'title' => "Season ".sprintf("%02d", $season),
                'seasonnumber' => $season,
            ];
            self::createNfoFile($seasonPath, $season);

            if(!file_exists($seasonPath))
                mkdir($seasonPath, 0777, true);

            foreach($episodes as $episode){
                self::createNfoFile($seasonPath, $episode);
                self::createStrmFile($seasonPath, $episode);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected static function createNfoFile(string $directory, array $imdbData): ?string {
        $typeMap = ['movie' => 'movie', 'tvSeries' => 'tvshow', 'tvSeason' => 'season', 'tvEpisode' => 'episodedetails'];
        if(in_array($imdbData['type'], array_keys($typeMap))){
            try {
                $type = $typeMap[$imdbData['type']];
                $filePath = $directory . "/" . $type . ".nfo";

                if($type == "episodedetails") {
                    $fileName = $imdbData['parent_imdb_id'].":".$imdbData['season'].":".$imdbData['episode'];
                    $filePath = $directory . "/" . $fileName . ".nfo";
                }

                if (!file_exists($directory))
                    mkdir($directory, 0777, true);

                unset($imdbData['id']);
                unset($imdbData['seasons']);
                unset($imdbData['totalSeasons']);
                unset($imdbData['totalEpisodes']);
                $imdbData['lockdata'] = "false";

                if($type !== "season" && $type !== "episodedetails") {
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
    public static function createStrmFile(string $directory, array $imdbData): ?string {
        try {
            $filePath = $directory . "/" . @$imdbData['imdb_id'] . ".strm";
            $streamUrl = '/stream?';
            $fileContent = $streamUrl.http_build_query([
                'imdbId' => @$imdbData['imdb_id']
            ]);

            if ($imdbData['type'] == "tvEpisode") {
                $fileName = $imdbData['parent_imdb_id'].":".$imdbData['season'].":".$imdbData['episode'];
                $filePath = $directory . "/" . $fileName . ".strm";

                $fileContent = $streamUrl.http_build_query([
                    'imdbId' => $fileName,
                ]);
            }

            if (!file_exists($filePath))
                file_put_contents($filePath, $fileContent);

            return $filePath;
        }catch (\Exception $e){}
        return null;
    }

}
