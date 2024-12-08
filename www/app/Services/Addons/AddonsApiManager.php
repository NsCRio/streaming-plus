<?php

namespace App\Services\Addons;

use App\Services\Api\AbstractApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AddonsApiManager extends AbstractApiManager
{

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
                'Name' => '[Streamio Addon] '.$addon['manifest']['name'],
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
                        'repositoryName' => $addon['repository']['Name'],
                        'repositoryUrl' =>  $addon['repository']['Url'],
                        'sourceUrl' =>  $addon['repository']['Url'],
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
                $addon = [
                    'repository' => $repo,
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
        if(Cache::has('mm_movie_'.md5($imdbId)))
            return Cache::get('mm_movie_'.md5($imdbId));

        $response = $this->apiCall('/stream/movie/'.trim($imdbId).'.json');
        if(!empty($response)){
            Cache::put('mm_movie_'.md5($imdbId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return null;
    }

    public function getSeriesEpisode(string $imdbId, int $season, int $episode){
        $imdbId = urlencode($imdbId.':'.$season.':'.$episode);
        if(Cache::has('mm_episode_'.md5($imdbId)))
            return Cache::get('mm_episode_'.md5($imdbId));

        $response = $this->apiCall('/stream/series/'.trim($imdbId).'.json');
        if(!empty($response)){
            Cache::put('mm_episode_'.md5($imdbId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return null;
    }

}
