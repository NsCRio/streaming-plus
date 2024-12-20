<?php

namespace App\Services\Addons;

use App\Services\Api\AbstractApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AddonsApiManager extends AbstractApiManager
{
    public function __construct(string $endpoint = null){
        $this->endpoint = $endpoint;
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
        $api->setAuthenticationByApiKey();
        $repositories = array_filter($api->getPackagesRepositories() ?? [], function($repo){
            return $repo['Name'] !== "Jellyfin Stable";
        }) ?? [];
        foreach ($repositories as $repo) {
            $addon = Cache::remember('addon_'.md5($repo['Url']), Carbon::now()->addHours(24), function () use ($repo) {
                $response = self::call($repo['Url']);
                if (isset($response['id']) && isset($response['types'])) {
                    $url = parse_url($repo['Url']);
                    $config = str_replace('/manifest.json', '', substr($url['path'], 1));
                    $repository = [
                        'id' => md5($repo['Url']),
                        'name' => $repo['Name'],
                        'url' => $url['scheme'] . '://' . $url['host'],
                        'endpoint' => $url['scheme'] . '://' . $url['host'] . '/' . $config,
                        'host' => $url['host'],
                        'config' => $config,
                        'manifest' => $repo['Url']
                    ];
                    return [
                        'repository' => $repository,
                        'manifest' => $response,
                    ];
                }
                return null;
            });
            if(isset($addon))
                $addons[] = $addon;
        }
        return $addons;
    }

    public static function getAddonById(string $id){
        $addons = self::getAddons();
        return !empty($addons) ? @array_values(array_filter(array_map(function ($addon) use($id){
            return $addon['repository']['id'] == $id ? $addon : null;
        }, $addons)))[0] : [];
    }

    public static function getActiveAddonsIds(): array {
        $addons = self::getAddons();
        return !empty($addons) ? array_values(array_filter(array_map(function ($addon) {
            return $addon['repository']['id'];
        }, $addons))) : [];
    }

    public function getManifest(){
        return $this->apiCall('/manifest.json');
    }

    public function getTVChannelsList(string $genre = null){
        return $this->getCatalog('tv', 'tv_channels', $genre);
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

    public function getCatalog(string $type, string $id, string $genre = null){
        if(isset($genre)) {
            $response = $this->apiCall('/catalog/' . urlencode($type) . '/' . urlencode($id) . '/genre='.urlencode($genre).'.json');
        }else{
            $response = $this->apiCall('/catalog/' . urlencode($type) . '/' . urlencode($id) . '.json');
        }
        return @$response['metas'];
    }

}
