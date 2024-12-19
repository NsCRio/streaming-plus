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

        if(isset($header['authorization'])){
            $api = new JellyfinApiManager($header);
            $apiKey = $api->createApiKeyIfNotExists('streaming-plus');
            if(isset($apiKey['AccessToken']))
                JellyfinManager::saveApiKey($apiKey['AccessToken']);
        }

        return $next($request);
    }
}
