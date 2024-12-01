<?php

namespace App\Services\Jellyfin;

use App\Models\Jellyfin\ApiKeys;
use App\Services\Api\AbstractApiManager;
use Carbon\Carbon;
use GuzzleHttp\Client;

class JellyfinApiManager extends AbstractApiManager
{
    protected $endpoint, $accessToken;

    public function __construct(string $accessToken = null){
        $this->endpoint = "http://localhost:8097";

        if(is_null($accessToken))
            $accessToken = $this->getAccessToken();

        $this->accessToken = $accessToken;
    }

    private function getAccessToken(){
        try {
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
        }catch (\Exception $e){}
        return null;
    }

    protected function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = []) : array|null {
        $default_headers = [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen(json_encode($data)),
            'X-Emby-Token' => $this->accessToken,
        ];
        $headers = array_merge($default_headers, $headers);
        $method = $method == 'POST' ? 'POST_BODY' : $method;
        return parent::apiCall($uri, $method, $data, $headers);
    }

}
