<?php

namespace App\Services\Streams;

use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\Jellyfin\lib\MediaSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StreamsManager
{

    public static function searchStreamsByItemId(string $itemId = null, string $mediaSourceId = null){
        //return Cache::remember('streams_item_'.md5($itemId.$mediaSourceId), Carbon::now()->addMinutes(10), function() use ($itemId, $mediaSourceId) {
            $mediaSources = [];
            try {
                $item = JellyfinManager::getItemDetailById($itemId);
                if (isset($item)) {
                    $mediaSources = @$item['MediaSources'] ?? [];
                    $streams = self::getStreams($item['imdbId'], $mediaSourceId);

                    //Trasforms streams into Jellyfin media source item
                    foreach ($streams as $stream) {
                        $mediaSource = MediaSource::$CONFIG;
                        $mediaSource['Container'] = $stream['stream_container'];
                        $mediaSource['MediaSourceId'] = $stream['stream_md5'];
                        $mediaSource['ItemId'] = $itemId;
                        $mediaSource['ImdbId'] = $item['imdbId'];
                        $mediaSource['Id'] = JellyfinManager::encodeItemId([
                            'itemId' => $itemId,
                            'streamId' => $stream['stream_md5'],
                            'mediaSourceId' => $mediaSourceId,
                            'imdbId' => $item['imdbId'],
                        ]);
                        $mediaSource['Path'] = app_url('/stream?streamId=' . $stream['stream_md5']);
                        $mediaSource['Name'] = $stream['stream_title'];
                        $mediaSources[$stream['stream_md5']] = $mediaSource;
                    }

                    if (isset($mediaSourceId)) {
                        $mediaSources = array_filter(array_map(function ($source) use ($mediaSourceId) {
                            if (isset($source['MediaSourceId']))
                                return ($source['MediaSourceId'] == $mediaSourceId) ? $source : null;
                            return ($source['Id'] == $mediaSourceId) ? $source : null;
                        }, $mediaSources));
                    }

                    if (!empty($mediaSources)) {
                        foreach ($mediaSources as $key => $mediaSource) {
                            if(str_ends_with($item['Path'], '.strm')){
                                if($mediaSource['Name'] == $item['imdbId']){
                                    $mediaSources[$key]['Name'] = "Default";
                                    $mediaSources[$key]['Path'] = app_url('/stream?imdbId=' . $item['imdbId']);
                                }
                            }
                            if ($mediaSource['Container'] == "strm") {
                                $mediaSources[$key]['Protocol'] = "Http";
                                $mediaSources[$key]['Container'] = "hls";
                            }
                        }
                        $mediaSources = collect($mediaSources)->sortBy('Container')->toArray();
                    }
                }
            }catch (\Exception $e){}

            ksort($mediaSources);
            return array_values($mediaSources);
        //});
    }

    public static function searchStreamsFromAddons(string $imdbId, string $itemId = null){
        //return Cache::remember('streams_imdb_'.md5($imdbId.$itemId), Carbon::now()->addMinutes(10), function() use ($imdbId, $itemId) {
            $streams = [];

            try {
                $addons = AddonsApiManager::getAddons();
                foreach ($addons as $addon) {
                    $api = new AddonsApiManager($addon['repository']['endpoint']);
                    if (str_contains($imdbId, ':')) {
                        $sources = $api->getSeriesEpisode($imdbId) ?? [];
                    } else {
                        $sources = $api->getMovie($imdbId) ?? [];
                    }
                    if (!empty($sources)) {
                        foreach ($sources as $source) {
                            $stream = self::getStreamFromSource($source, $addon, $imdbId);
                            if (!empty($stream))
                                $streams[$stream['stream_md5']] = $stream;
                        }
                    }
                }
            }catch (\Exception $e){}

            return $streams;
        //});
    }


    public static function getStreams(string $imdbId = null, string $streamId = null)
    {
        $addons = AddonsApiManager::getAddons();
        $addonsIds = array_map(function ($addon) {
            return $addon['repository']['id'];
        }, $addons);

        $query = Streams::query()->whereIn('stream_addon_id', $addonsIds)
            ->where(function ($query) use($imdbId, $streamId){
                $query->where('stream_md5', $streamId)->orWhere('stream_imdb_id', $imdbId);
            })->where('created_at', '<=', Carbon::now()->addHour())->get()->toArray();

        $streams = [];
        foreach ($query as $stream) {
            $streams[$stream['stream_md5']] = $stream;
        }

        if(isset($imdbId))
            $streams = array_merge($streams, self::searchStreamsFromAddons($imdbId));

        return array_values($streams);
    }

    protected static function getStreamFromSource(array $source, array $addon = [], string $imdbId = null){
        if (isset($source['infoHash']) && isset($source['behaviorHints'])) {
            if(isset($source['behaviorHints']['filename'])){
                $file = pathinfo($source['behaviorHints']['filename']);
                if(in_array(@$file['extension'], ['mkv', 'mp4'])){
                    $source['url'] = urlencode("magnet:?xt=urn:btih:" . $source['infoHash']);
                    $source['url'] .= '?file=' . urlencode($source['behaviorHints']['filename']);
                }
            }
        }

        if(isset($source['url'])) {
            $file = pathinfo($source['url']);
            $container = @$file['extension'] ?? "hls";
            if(in_array(@$file['extension'], ['m3u', 'm3u8']))
                $container = 'hls';

            $title = @$source['name'];
            if(!empty(@$source['title']))
                $title .= " - " . @$source['title'];
            if(!empty(@$source['description']))
                $title .= " - " . @$source['description'];

            $stream = Streams::query()->where('stream_md5', md5(trim($source['url'])))->first();
            if (!isset($stream))
                $stream = new Streams();
            $stream->stream_md5 = md5(trim($source['url']));
            $stream->stream_url = $source['url'];
            $stream->stream_protocol = isset($source['infoHash']) ? "torrent" : "http";
            $stream->stream_container = $container;
            $stream->stream_addon_id = @$addon['repository']['id'];
            $stream->stream_imdb_id = $imdbId;
            $stream->stream_title = $title;
            $stream->stream_host = @$addon['repository']['host'];
            $stream->save();
            return $stream->toArray();
        }

        return [];
    }

}
