<?php

namespace App\Services\MammaMia;

use App\Services\Api\AbstractApiManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class MammaMiaApiManager extends AbstractApiManager
{
    protected $endpoint, $config;

    public function __construct(){
        $this->config = "|SC|SC_FS|LC|SW|TF|TF_FS|FT|AW|LIVETV|CB|DDL|WHVX|";
        $this->endpoint = "http://localhost:8080/".$this->config;
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
