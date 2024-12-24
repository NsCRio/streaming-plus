<?php

namespace App\Http\Middleware;

use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class JellyfinMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $header = $request->header();

        //Api key creation
        if(isset($header['authorization'])){
            if(!file_exists(config('jellyfin.api_key_path'))) {
                $hasCreation = Cache::has('api_key_creation');
                if(!$hasCreation) {
                    Cache::put('api_key_creation', true);
                    $api = new JellyfinApiManager($header);
                    $apiKey = $api->createApiKeyIfNotExists(config('app.code_name'));
                    if (isset($apiKey['AccessToken']))
                        JellyfinManager::saveApiKey($apiKey['AccessToken']);
                    Cache::forget('api_key_creation');
                }
            }
        }

        $userAgent = str_replace(' ', '-',@explode('/', trim(@$header['user-agent'][0]))[0] ?? "");
        if (!empty($userAgent) && in_array(strtoupper($userAgent), ['KTOR-CLIENT', 'INFUSE-LIBRARY', 'INFUSE-DIRECT'])) { //Fix for Findroid & Infuse
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                $transformedData = $this->transformEmptyArraysToNull($data);
                $response->setData($transformedData);
            }
        }

        return $response;
    }

    private function transformEmptyArraysToNull($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->transformEmptyArraysToNull($value);
            }
            return empty($data) ? null : $data;
        }
        return $data;
    }
}
