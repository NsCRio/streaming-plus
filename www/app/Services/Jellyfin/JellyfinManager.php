<?php

namespace App\Services\Jellyfin;

use App\Models\Items;
use App\Services\Items\ItemsSearchManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use LaLit\Array2XML;

class JellyfinManager
{
    public static $typesMap = ['Movie' => 'movie', 'Series' => 'tvSeries'];

    /**
     * @throws \Exception
     */
    public static function getItemsFromSearchTerm($searchTerm, $itemType, array $jellyfinSearch = []) : null|array {
        $cKey = md5($searchTerm.$itemType.json_encode($jellyfinSearch));
        return Cache::remember('jellyfin_search_'.$cKey, Carbon::now()->addSeconds(10), function () use ($searchTerm, $itemType, $jellyfinSearch) {
            $search = new ItemsSearchManager($searchTerm, self::$typesMap[$itemType]);
            $results = $search->search()->getResults();

            $response = [
                'Items' => [],
                'StartIndex' => 0
            ];
            if(!empty($results)){
                $api = new JellyfinApiManager();
                $system = $api->getSystemInfo();

                $jellyfinIds = [];
                if(!empty($jellyfinSearch['Items'])){
                    $jellyfinIds = array_filter(array_map(function($item){
                        return $item['Id'] ?? null;
                    }, $jellyfinSearch['Items']));
                }

                foreach ($results as $result) {
                    if((isset($result->item_jellyfin_id) && in_array($result->item_jellyfin_id, $jellyfinIds)))
                        continue;

                    $response['Items'][] = [
                        'Name' => $result->item_title,
                        'ServerId' => $system['Id'],
                        'Id' => $result->item_jellyfin_id ?? $result->item_md5,
                        'PremiereDate' => $result->item_year."-01-01T00:00:00.0000000Z",
                        'CriticRating' => null,
                        'OfficialRating' => null,
                        'ChannelId' => null,
                        'CommunityRating' => null,
                        'ProductionYear' => $result->item_year,
                        'IsFolder' => false,
                        'Type' => 'Unknown',
                        'PrimaryImageAspectRatio' => 0.7,
                        'UserData' => [
                            'PlaybackPositionTicks' => 0,
                            'PlayCount' => 0,
                            'IsFavorite' => isset($result->item_path),
                            'Played' => false,
                            'Key' => null,
                            'ItemId' => '00000000000000000000000000000000'
                        ],
                        'VideoType' => 'Unknown',
                        'ImageTags' => [
                            "Primary" => $result->item_image_md5,
                        ],
                        'LocationType' => 'FileSystem',
                        'MediaType' => 'Unknown',
                    ];
                }
            }

            if(!empty($jellyfinSearch['Items']))
                $response['Items'] = array_merge($jellyfinSearch['Items'], $response['Items']);

            $response['TotalRecordCount'] = count($response['Items']);
            return $response;
        });
    }


    public static function getItemById(string $itemId, array $query = []): null|array {
        return Cache::remember('jellyfin_item_'.md5($itemId.json_encode($query)), Carbon::now()->addSeconds(10), function () use ($itemId, $query) {
            $outcome = [];
            $api = new JellyfinApiManager();

            if (!empty($query)) {
                $userId = $query['userId'] ?? null;
                if (isset($userId)) {
                    unset($query['userId']);
                    $outcome = $api->getUsersItems($userId, $itemId, $query);
                } else {
                    $outcome = $api->getItem($itemId, $query);
                }

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

            if (empty($outcome)) {
                $item = Items::where('item_md5', $itemId)->first();
                if (isset($item)) {
                    $system = $api->getSystemInfo();
                    $imdbData = $item->getImdbData();
                    $overview = "<span style='color: #8e2f96'><b>Click on the Heart icon to add this item to the library.</b></span>";

                    $outcome = \App\Services\Jellyfin\lib\Items::$CONFIG;
                    $outcome['CommunityRating'] = @$imdbData['rating'];
                    $outcome['DateCreated'] = Carbon::parse($item->created_at)->timestamp;
                    $outcome['ProductionYear'] = $item->item_year;
                    $outcome['ExternalUrls'][] = [
                        'Name' => 'IMDb',
                        'Url' => 'https://www.imdb.com/title/' . $item->item_imdb_id,
                    ];
                    $outcome['Genres'] = @$imdbData['genre'];
                    $outcome['Id'] = $item->item_md5;
                    $outcome['ImageTags']['Primary'] = $item->item_image_md5;
                    $outcome['Name'] = $item->item_title;
                    $outcome['OriginalTitle'] = $item->item_original_title;
                    $outcome['Overview'] = $overview . "\n\n" . @$imdbData['plot'];
                    $outcome['ParentId'] = $item->item_md5;
                    $outcome['ProviderIds']['Imdb'] = $item->item_imdb_id;
                    $outcome['ServerId'] = $system['Id'];
                    $outcome['SortName'] = $item->item_title;
                    $outcome['Type'] = 'Unknown';
                    $outcome['Path'] = null;
                    $outcome['MediaStreams'] = null;
                    $outcome['MediaSources'] = null;
                    $outcome['UserData'] = [
                        'PlaybackPositionTicks' => 0,
                        'PlayCount' => 0,
                        'IsFavorite' => isset($item->item_path),
                        'Played' => false,
                        'Key' => null,
                        'ItemId' => '00000000000000000000000000000000'
                    ];

                }
            }

            return $outcome;
        });
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
            $filePath .= (!empty($streamName) ? ' - ' . $streamName : "") . ".strm";
            $streamUrl = config('app.url').'/stream?';
            $fileContent = $streamUrl.http_build_query([
                'imdb_id' => @$imdbData['imdb_id'],
                'provider' => (!empty($streamName) ? md5($streamName) : "")
            ]);

            if ($imdbData['type'] == "tvEpisode") {
                $fileName = 'Episode S' . sprintf("%02d", $imdbData['season']) . 'E' . sprintf("%02d", $imdbData['episode']);
                $filePath = $directory . "/" . $fileName;
                $filePath .= (!empty($streamName) ? ' - ' . $streamName : "") . ".strm";

                $fileContent = $streamUrl.http_build_query([
                    'imdbId' => @$imdbData['parent_imdb_id'],
                    'season' => $imdbData['season'],
                    'episode' => $imdbData['episode'],
                    'provider' => (!empty($streamName) ? md5($streamName) : "")
                ]);
            }

            if (!file_exists($filePath))
                file_put_contents($filePath, $fileContent);

            return $filePath;
        }catch (\Exception $e){}
        return null;
    }

}
