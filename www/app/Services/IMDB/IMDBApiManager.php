<?php

namespace App\Services\IMDB;

use App\Services\Api\AbstractApiManager;
use App\Services\Scraper\ProxyManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class IMDBApiManager extends AbstractApiManager
{
    protected $endpoint;
    protected $types = [
        'movie',
        'tvSeries'
    ];

    public function __construct(){
        $this->endpoint = "https://v3.sg.media-imdb.com";
    }

    public function search(string $searchTerm, string $type = null, int $limit = 5){
        if(Cache::has('imdb_search_'.md5($searchTerm.$type.$limit)))
            return Cache::get('imdb_search_'.md5($searchTerm.$type.$limit));

        $uri = '/suggestion/x/'.urlencode($searchTerm).'.json';
        $response = $this->apiCall($uri, 'GET', ['includeVideos' => 0]);
        if(!empty($response['d'])) {
            $outcome = array_filter(array_slice($response['d'], 0, $limit), function($item) {
                return in_array(@$item['qid'], $this->types);
            });
            if(isset($type) && in_array($type, $this->types)) {
                $outcome = array_filter($outcome, function($item) use ($type) {
                   return @$item['qid'] == $type;
                });
            }
            $outcome = array_values($outcome);
            Cache::put('imdb_search_'.md5($searchTerm.$type.$limit), $outcome, Carbon::now()->addDay());
            return $outcome;
        }
        return null;
    }

    protected function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        $platforms = ['macOS', 'Windows', 'Android', 'iOS'];
        $default_headers = [
            'Referer' => 'https://www.imdb.com/',
            'User-Agent' => $this->getRandomAgent(),
            'Accept' => 'application/json, text/plain, */*',
            'sec-ch-ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => $platforms[array_rand($platforms)],
        ];
        $headers = array_merge($default_headers, $headers);
        return parent::apiCall($uri, $method, $data, $headers);
    }
}
