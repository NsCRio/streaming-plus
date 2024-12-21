<?php

namespace App\Http\Middleware;

use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Closure;
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

        return $next($request);
    }
}
