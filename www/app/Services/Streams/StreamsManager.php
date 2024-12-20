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
        $addonIds = AddonsApiManager::getActiveAddonsIds();

        if(Cache::has('streams_item_'.md5($itemId.$mediaSourceId.json_encode($addonIds))))
            return Cache::get('streams_item_'.md5($itemId.$mediaSourceId.json_encode($addonIds)));

        $mediaSources = [];
        try {
            $item = JellyfinManager::getItemDetailById($itemId);
            if (!empty($item)) {
                $mediaSources = @$item['MediaSources'] ?? [];
                $streams = self::getStreams($item['imdbId'], $mediaSourceId);

                foreach ($streams as $stream) {
                    $mediaSource = MediaSource::$CONFIG;
                    $mediaSource['UrlProtocol'] = $stream['stream_protocol'];
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
                    $mediaSource['Name'] = '['.strtoupper($stream['stream_protocol']).'] '.$stream['stream_title'];
                    $mediaSource['LastPlayed'] = @$stream['stream_watched_at'];
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
                        if ($mediaSource['Container'] == "strm") {
                            $mediaSources[$key]['Protocol'] = "Http";
                            $mediaSources[$key]['Container'] = "hls";
                        }
                        if(str_ends_with($item['Path'], '.strm')){
                            if($mediaSource['Name'] == $item['imdbId']){
                                unset($mediaSources[$key]);
//                                $mediaSources[$key]['Name'] = "Last Played";
//                                $mediaSources[$key]['Path'] = app_url('/stream?imdbId=' . $item['imdbId']);
                            }
                        }
                    }
                }
            }
        }catch (\Exception $e){}

        $mediaSources = collect($mediaSources)->sortBy('UrlProtocol')->sortBy('Name', SORT_NATURAL)->all();
        $mediaSources = array_values($mediaSources);
        if(!empty($mediaSources))
            Cache::put('streams_item_'.md5($itemId.$mediaSourceId.json_encode($addonIds)), $mediaSources, Carbon::now()->addMinutes(10));

        return $mediaSources;
    }

    public static function searchStreamsFromAddons(string $imdbId, string $mediaSourceId = null){
        $addonIds = AddonsApiManager::getActiveAddonsIds();
        if(Cache::has('streams_addons_'.md5($imdbId.$mediaSourceId.json_encode($addonIds))))
            return Cache::get('streams_addons_'.md5($imdbId.$mediaSourceId.json_encode($addonIds)));

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
                        $stream = self::getStreamFromSource($source, $addon, $imdbId, $mediaSourceId);
                        if (!empty($stream))
                            $streams[$stream->stream_md5] = $stream;
                    }
                }
            }
        }catch (\Exception $e){}

        if(!empty($streams))
            Cache::put('streams_addons_'.md5($imdbId.$mediaSourceId.json_encode($addonIds)), $streams, Carbon::now()->addMinutes(10));

        return $streams;
    }


    public static function getStreams(string $imdbId = null, string $mediaSourceId = null)
    {
        $addonIds = AddonsApiManager::getActiveAddonsIds();
        $query = Streams::query()->whereIn('stream_addon_id', $addonIds)
            ->where(function ($query) use ($imdbId, $mediaSourceId) {
                $query->where('stream_md5', $mediaSourceId)->orWhere('stream_imdb_id', $imdbId);
            })->where('created_at', '<=', Carbon::now()->addDay())->get();

        $streams = [];
        foreach ($query as $stream) {
            $streams[$stream->stream_md5] = $stream;
        }

        if (isset($imdbId))
            $streams = array_merge($streams, self::searchStreamsFromAddons($imdbId, $mediaSourceId));

        $streams = collect($streams)->sortBy('stream_addon_id')
            ->sortBy('stream_protocol')->sortBy('stream_title')->toArray();

        return array_values($streams);
    }

    protected static function getStreamFromSource(array $source, array $addon = [], string $imdbId = null, string $mediaSourceId = null){
        if (isset($source['infoHash'])) {
            $source['url'] = urlencode("magnet:?xt=urn:btih:" . $source['infoHash']);
            if(isset($source['behaviorHints']['filename']))
                $source['url'] .= '?file=' . urlencode($source['behaviorHints']['filename']);
        }

        if(isset($source['url'])) {
            $file = @pathinfo(@parse_url($source['url'])['path']);
            $container = 'hls';
            if(!empty(@$file['extension']) && in_array(@$file['extension'], config('jellyfin.supported_extensions')))
                $container = $file['extension'];

            $title = @$source['name'];
            if(!empty(@$source['title']))
                $title .= " - " . @$source['title'];
            if(!empty(@$source['description']))
                $title .= " - " . @$source['description'];

            $stream = Streams::query()->where('stream_md5', md5(json_encode($source)))->first();
            if (!isset($stream))
                $stream = new Streams();

            $stream->stream_md5 = md5(json_encode($source));
            $stream->stream_url = $source['url'];
            $stream->stream_protocol = isset($source['infoHash']) ? "torrent" : "http";
            $stream->stream_container = $container;
            $stream->stream_addon_id = @$addon['repository']['id'];
            $stream->stream_imdb_id = $imdbId;
            $stream->stream_jellyfin_id = $mediaSourceId;
            $stream->stream_title = $title;
            $stream->stream_host = @$addon['repository']['host'];
            $stream->save();

            return $stream;
        }

        return [];
    }

}
