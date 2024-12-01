<?php

namespace App\Services\IMDB;

use App\Services\Scraper\ProxyManager;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class IMDBApiManager
{
    protected $endpoint;

    public function __construct(){
        $this->endpoint = "https://v3.sg.media-imdb.com";
    }

    public function search($searchTerm, $limit = 5){
        if(Cache::has('imdb_search_'.md5($searchTerm)))
            return Cache::get('imdb_search_'.md5($searchTerm));

        $uri = '/suggestion/x/'.urlencode($searchTerm).'.json';
        $response = $this->apiCall($uri, 'GET', ['includeVideos' => 0]);
        if(!empty($response['d'])) {
            $outcome = array_slice($response['d'], 0, $limit);
            Cache::get('imdb_search_'.md5($searchTerm), $outcome);
            return $outcome;
        }
        return null;
    }

    public static function call(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        $api = new self();
        return $api->apiCall($uri, $method, $data, $headers);
    }

    private function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        try {
            $cli = new Client();
            $timeout = 60;
            $platforms = ['macOS', 'Windows', 'Android', 'iOS'];
            $uri = !str_starts_with('/', $uri) ? $uri : '/' . $uri;
            $uri = sprintf("%s?%s", $uri, http_build_query($data));
            $default_headers = [
                'sec-ch-ua-platform' => $platforms[array_rand($platforms)],
                'Referer' => 'https://www.imdb.com/',
                'User-Agent' => ProxyManager::getRandomAgent(),
                'Accept' => 'application/json, text/plain, */*',
                'sec-ch-ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
            ];
            $headers = array_merge($default_headers, $headers);
            $r = $cli->request($method, $this->endpoint . $uri, [
                'connect_timeout' => $timeout,
                'headers' => $headers,
                //'body' => $data,
            ]);
            $res = (string)$r->getBody();
            $res = json_decode($res, true);
            return $res;
        }catch (\Exception $e){}
        return null;
    }
}
