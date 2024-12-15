<?php

namespace App\Services\Streams;

use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\lib\MediaSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StreamsManager
{

    public static function searchStreamsByItemId(string $itemId = null, string $mediaSourceId = null, string $imdbId = null){
        return Cache::remember('streams_item_'.md5($itemId.$mediaSourceId.$imdbId), Carbon::now()->addMinutes(10), function() use ($itemId, $mediaSourceId) {
            $mediaSources = [];

            try {
                $api = new JellyfinApiManager();
                $detail = $api->getItem($itemId);

                if (isset($detail)) {
                    //Get ImdbId from Jellyfin Item
                    if (!isset($imdbId)) {
                        $imdbId = @$detail['ProviderIds']['Imdb'];
                        if (isset($detail['SeriesId']) && isset($detail['SeasonId'])) {
                            $imdbId = null;
                            $query = $api->getItemFromQuery($detail['SeriesId']);
                            if (!empty($query)) {
                                $imdbId = @$query['ProviderIds']['Imdb'];
                                $imdbId = $imdbId . ':' . $detail['ParentIndexNumber'] . ':' . $detail['IndexNumber'];
                            }
                        }
                    }

                    $mediaSources = @$detail['MediaSources'] ?? [];
                    $streams = self::getStreams($imdbId, $itemId, $mediaSourceId);

                    //Trasforms streams into Jellyfin media source item
                    foreach ($streams as $stream) {
                        $mediaSource = MediaSource::$CONFIG;
                        if (!empty($detail['MediaSources']))
                            $mediaSource = $detail['MediaSources'][array_key_first($detail['MediaSources'])];
                        $mediaSource['Container'] = $stream['stream_container'];
                        //$mediaSource['ETag'] = $stream['stream_md5'];
                        $mediaSource['MediaSourceId'] = $stream['stream_md5'];
                        $mediaSource['ItemId'] = $itemId;
                        $mediaSource['ImdbId'] = $imdbId;
                        $mediaSource['Id'] = 'strm_' . $stream['stream_md5'];
                        if (isset($itemId))
                            $mediaSource['Id'] .= '-iid_' . $itemId;
                        if (isset($mediaSourceId))
                            $mediaSource['Id'] .= '-msid_' . $mediaSourceId;
                        if (isset($imdbId))
                            $mediaSource['Id'] .= '-imdbid_' . $imdbId;
                        $mediaSource['Id'] = base64_encode($mediaSource['Id']);
                        $mediaSource['Path'] = app_url('/stream?streamId=') . $stream['stream_md5'];
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
                }

                if (!empty($mediaSources)) {
                    foreach ($mediaSources as $key => $mediaSource) {
                        if ($mediaSource['Container'] == "strm") {
                            $mediaSources[$key]['Container'] = "hls";
                        }
                        if (str_starts_with($mediaSource['Path'], config('app.url'))) {
                            $mediaSources[$key]['Name'] = "Random";
                            $mediaSources[$key]['Path'] = str_replace(config('app.url'), app_url(), $mediaSource['Path']);
                            if (count($mediaSources) > 1)
                                unset($mediaSources[$key]);
                        }
                    }
                    $mediaSources = collect($mediaSources)->sortBy('Container')->toArray();
                }
            }catch (\Exception $e){}

            return array_values($mediaSources);
        });
    }

    public static function searchStreamsFromAddons(string $imdbId, string $itemId = null){
        return Cache::remember('streams_imdb_'.md5($imdbId.$itemId), Carbon::now()->addMinutes(10), function() use ($imdbId, $itemId) {
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
                            $stream = self::getStreamFromSource($source, $addon, $imdbId, $itemId);
                            if (!empty($stream))
                                $streams[$stream['stream_md5']] = $stream;
                        }
                    }
                }
            }catch (\Exception $e){}

            return $streams;
        });
    }


    public static function getStreams(string $imdbId = null, string $itemId = null, string $mediaSourceId = null)
    {
        $query = Streams::query();
        if (isset($mediaSourceId)) {
            $query->where('stream_md5', $mediaSourceId);
        } else {
            $query->where(function ($query) use ($imdbId, $itemId) {
                $query->where('stream_jellyfin_id', $itemId)
                    ->orWhere('stream_imdb_id', $imdbId);
            });
        }
        $query->where('created_at', '<=', Carbon::now()->addHour())->get()->toArray();

        $streams = [];
        foreach ($query as $stream) {
            $streams[$stream['stream_md5']] = $stream;
        }

        if(isset($imdbId))
            $streams = array_merge($streams, self::searchStreamsFromAddons($imdbId, $itemId));

        return array_values($streams);
    }

    protected static function getStreamFromSource(array $source, array $addon = [], string $imdbId = null, string $itemId = null){
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

            $stream = Streams::query()->where('stream_md5', md5($source['url']))->first();
            if (!isset($stream))
                $stream = new Streams();
            $stream->stream_md5 = md5($source['url']);
            $stream->stream_url = $source['url'];
            $stream->stream_protocol = isset($source['infoHash']) ? "torrent" : "http";
            $stream->stream_container = $container;
            $stream->stream_addon_id = $addon['repository']['id'];
            if (isset($itemId))
                $stream->stream_jellyfin_id = $itemId;
            $stream->stream_imdb_id = $imdbId;
            $stream->stream_title = $title;
            $stream->stream_host = $addon['repository']['host'];
            $stream->save();
            return $stream->toArray();
        }

        return [];
    }

}
