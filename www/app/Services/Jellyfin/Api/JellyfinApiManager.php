<?php

namespace App\Services\Jellyfin\Api;

use App\Models\Jellyfin\ApiKeys;
use Carbon\Carbon;
use GuzzleHttp\Client;

class JellyfinApiManager
{
    protected $endpoint, $accessToken;

    public function __construct(string $accessToken = null){
        $this->endpoint = "http://localhost:8097";

        if(is_null($accessToken))
            $accessToken = $this->getAccessToken();

        $this->accessToken = $accessToken;
    }

    private function getAccessToken(){
        //try {
            $apikey = ApiKeys::query()->where('Name', 'streaming-plus')
                ->orWhere('AccessToken', md5('streaming-plus'))
                ->orderBy('DateCreated')->first();
            if (!isset($apikey)){
                $apikey = new ApiKeys();
                $apikey->DateCreated = Carbon::now()->format('Y-m-d H:i:s.u') . '0';
                $apikey->DateLastActivity = Carbon::now()->format('Y-m-d H:i:s');
                $apikey->Name = "streaming-plus";
                $apikey->AccessToken = md5('streaming-plus');
                $apikey->save();
            }
            return $apikey->AccessToken;
        //}catch (\Exception $e){}
        return null;
    }

    public static function call(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        $api = new self();
        return $api->apiCall($uri, $method, $data, $headers);
    }

    private function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        //try {
            $cli = new Client();
            $timeout = 5;
            $data = json_encode($data);
            $uri = !str_starts_with('/', $uri) ? $uri : '/' . $uri;
            $default_headers = [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
                'X-Emby-Token' => $this->accessToken,
            ];
            $headers = array_merge($default_headers, $headers);
            $r = $cli->request($method, $this->endpoint . $uri, [
                'connect_timeout' => $timeout,
                'headers' => $headers,
                'body' => $data,
            ]);
            $res = (string)$r->getBody();
            $res = json_decode($res, true);
            return $res;
        //}catch (\Exception $e){}
        return null;
    }

}
