<?php

namespace App\Services\Addons;

use App\Models\Addons;
use App\Models\Items;
use App\Models\Streams;
use App\Services\Api\AbstractApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\lib\MediaSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AddonsApiManager extends AbstractApiManager
{

    public function searchStreamByItemId(string $itemId = null, string $mediaSourceId = null){
        //return Cache::remember('stream_item_'.md5($itemId.$mediaSourceId), Carbon::now()->addMinutes(10), function() use ($itemId, $mediaSourceId) {
            $mediaSources = [];

            $api = new JellyfinApiManager();
            $detail = $api->getItem($itemId);

            if (isset($detail)) {

                $imdbId = @$detail['ProviderIds']['Imdb'];
                if (isset($detail['SeriesId']) && isset($detail['SeasonId'])) {
                    $imdbId = null;
                    $query = $api->getItemFromQuery($detail['SeriesId']);
                    if (!empty($query)) {
                        $imdbId = @$query['ProviderIds']['Imdb'];
                        $imdbId = $imdbId . ':' . $detail['ParentIndexNumber'] . ':' . $detail['IndexNumber'];
                    }
                }

                $mediaSources = @$detail['MediaSources'] ?? [];

                $streams = Streams::query();
                if (isset($mediaSourceId)) {
                    $streams->where('stream_md5', $mediaSourceId);
                } else {
                    $streams->where(function ($query) use ($imdbId, $itemId) {
                        $query->where('stream_jellyfin_id', $itemId)
                            ->orWhere('stream_imdb_id', $imdbId);
                    });
                }

                $streams = $streams->where('created_at', '<=', Carbon::now()->addHour())->get()->toArray();
                if (isset($imdbId))
                    $streams = array_merge($streams, $this->searchStreamByImdbId($imdbId, $itemId));

                foreach ($streams as $stream) {
                    $mediaSource = $detail['MediaSources'][array_key_first($detail['MediaSources'])];
                    $mediaSource['Container'] = $mediaSource['Container'] !== "strm" ? $mediaSource['Container'] : 'hls';
                    $mediaSource['Id'] = $stream['stream_md5'];
                    $mediaSource['Path'] = $stream['stream_url'];
                    $mediaSource['Name'] = $stream['stream_title'];
                    $mediaSources[$stream['stream_md5']] = $mediaSource;
                }

                if (isset($mediaSourceId)) {
                    $mediaSources = array_filter(array_map(function ($source) use ($mediaSourceId) {
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
                        $mediaSources[$key]['Name'] = "Auto";
                        $mediaSources[$key]['Path'] = get_last_url($mediaSource['Path']);
                    }
                }
            }

            return array_values($mediaSources);
        //});
    }

    public function searchStreamByImdbId(string $imdbId, string $itemId = null){
        return Cache::remember('stream_imdb_'.md5($imdbId.$itemId), Carbon::now()->addMinutes(10), function() use ($imdbId, $itemId) {
            $streams = [];
            $sources = [];
            $addons = self::getAddons();
            foreach ($addons as $addon) {
                $this->endpoint = $addon['repository']['endpoint'];
                if (str_contains($imdbId, ':')) {
                    $sources = array_merge($sources, $this->getSeriesEpisode($imdbId) ?? []);
                } else {
                    $sources = array_merge($sources, $this->getMovie($imdbId) ?? []);
                }
                if (!empty($sources)) {
                    foreach ($sources as $source) {
                        if (isset($source['infoHash'])) //Per il momento skippo i torrent
                            continue;

                        $stream = Streams::query()
                            ->where('stream_md5', md5(json_encode($source)))
                            ->first();
                        if (!isset($stream))
                            $stream = new Streams();
                        $stream->stream_md5 = md5(json_encode($source));
                        $stream->stream_url = $source['url'];
                        $stream->stream_protocol = "http";
                        $stream->stream_container = "hls";
                        $stream->stream_addon_id = $addon['repository']['id'];
                        if (isset($itemId))
                            $stream->stream_jellyfin_id = $itemId;
                        $stream->stream_imdb_id = $imdbId;
                        $stream->stream_title = @$source['name'] . " - " . @$source['title'];
                        $stream->stream_host = $addon['repository']['host'];
                        $stream->save();
                        $streams[md5(json_encode($source))] = $stream->toArray();
                    }
                }
            }
            return array_values($streams);
        });
    }

    public static function getAddonsFromPlugins(){
        $plugins = [];
        $addons = self::getAddons();
        foreach($addons as $addon){
            $plugins[] = [
                'CanUninstall' => false,
                //'ConfigurationFileName' => 'Jellyfin.Plugin.Test.xml',
                'Description' => $addon['manifest']['description'],
                'HasImage' => false,
                'Id' => md5($addon['manifest']['id']),
                'Name' => '<span style="color: #af39ae">[Streamio Addon]</span> ' .$addon['manifest']['name'],
                'Status' => 'Active',
                'Version' => $addon['manifest']['version'],
            ];
        }
        return $plugins;
    }

    public static function getAddonsFromPackages(){
        $packages = [];
        $addons = self::getAddons();
        foreach($addons as $addon){
            $packages[] = [
                'category' => "Streamio Addons",
                'description' => $addon['manifest']['description'],
                'guid' => md5($addon['manifest']['id']),
                'name' => $addon['manifest']['name'],
                'imageUrl' => @$addon['manifest']['background'] ?? @$addon['manifest']['logo'],
                'overview' => "",
                'owner' => $addon['manifest']['name'],
                'versions' => [
                    [
                        'VersionNumber' => $addon['manifest']['version'],
                        'changelog' => "",
                        'checksum' => md5($addon['manifest']['id']),
                        'repositoryName' => $addon['repository']['name'],
                        'repositoryUrl' =>  $addon['repository']['url'],
                        'sourceUrl' =>  $addon['repository']['url'],
                        'targetAbi' =>  $addon['manifest']['version'],
                        'timestamp' =>  Carbon::now()->timestamp,
                        'version' => $addon['manifest']['version'],
                    ]
                ]
            ];
        }
        return $packages;
    }

    public static function getAddons(){
        $addons = [];
        $api = new JellyfinApiManager();
        $repositories = array_filter($api->getPackagesRepositories(), function($repo){
            return $repo['Name'] !== "Jellyfin Stable";
        }) ?? [];
        foreach ($repositories as $repo) {
            $response = self::call($repo['Url']);
            if(isset($response['id']) && isset($response['types'])){
                $url = parse_url($repo['Url']);
                $config = str_replace('/manifest.json', '', substr($url['path'], 1));
                $repository = [
                    'id' => md5($repo['Url']),
                    'name' => $repo['Name'],
                    'url' => $url['scheme'].'://'.$url['host'],
                    'endpoint' => $url['scheme'].'://'.$url['host'].'/'.$config,
                    'host' => $url['host'],
                    'config' => $config,
                    'manifest' => $repo['Url']
                ];
                $addon = [
                    'repository' => $repository,
                    'manifest' => $response,
                ];
                $addons[] = $addon;
            }
        }
        return $addons;
    }

    public function getManifest(){
        return $this->apiCall('/manifest.json');
    }

    public function getTVChannelsList(string $genre = null){
        if(isset($genre)){
            $response = $this->apiCall('/catalog/tv/tv_channels/genre='.urlencode($genre).'.json');
        }else{
            $response = $this->apiCall('/catalog/tv/tv_channels.json');
        }
        return @$response['metas'];
    }

    public function getTVChannel(string $channelId){
        $response = $this->apiCall('/meta/tv/'.$channelId.'.json');
        return @$response['meta'];
    }

    public function getMovie(string $imdbId){
        if(Cache::has('mm_movie_'.md5($this->endpoint.$imdbId)))
            return Cache::get('mm_movie_'.md5($this->endpoint.$imdbId));

        $response = $this->apiCall('/stream/movie/'.trim($imdbId).'.json');
        if(!empty($response)){
            Cache::put('mm_movie_'.md5($this->endpoint.$imdbId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return null;
    }

    public function getSeriesEpisode(string $imdbId, string $season = null, string $episode = null){
        if(isset($season) && isset($episode))
            $imdbId = $imdbId.':'.$season.':'.$episode;

        if(Cache::has('mm_episode_'.md5($this->endpoint.$imdbId)))
            return Cache::get('mm_episode_'.md5($this->endpoint.$imdbId));

        $response = $this->apiCall('/stream/series/'.urlencode(trim($imdbId)).'.json');
        if(!empty($response)){
            Cache::put('mm_episode_'.md5($this->endpoint.$imdbId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return null;
    }

}
