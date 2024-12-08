<?php

namespace App\Http\Controllers;

use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\StreamingPlus\ItemsSearchManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JellyfinController extends Controller
{

    /**
     * @throws \Exception
     */
    public function getItems(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $query = $request->all();
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        $itemType = $request->get('includeItemTypes');
        $isMissing = $request->get('isMissing', false);
        $isSearching = Cache::has("jellyfin_searching_".md5($searchTerm));
        $hasResult = Cache::has("jellyfin_search_".md5($searchTerm));

        $api = new JellyfinApiManager();

        if(in_array(trim($itemType), ["Movie", "Series"])) {
            if (!$isSearching && !$hasResult && !$isMissing) {
                //sleep(5);
                Cache::put("jellyfin_searching_" . md5($searchTerm), $searchTerm);

                $search = new ItemsSearchManager($searchTerm);
                $results = $search->search()->getResults();

                if (!empty($results)) {
                    $api->startLibraryScan();

                    if(str_starts_with($searchTerm, "tt")){
                        $query['searchTerm'] = @$results[0]->item_title;
                        Cache::put("jellyfin_tt_" . md5($searchTerm), $query['searchTerm'], Carbon::now()->addDay());
                    }

                    //sleep(count($results)*20);
                    Cache::put("jellyfin_search_" . md5($searchTerm), $results, Carbon::now()->addHour());
                }

                Cache::forget("jellyfin_searching_" . md5($searchTerm));
            }
        }

        if(Cache::has("jellyfin_tt_".md5($searchTerm))){
            $query['searchTerm'] = Cache::get("jellyfin_tt_".md5($searchTerm), $searchTerm);
        }

        $response = $api->getItems($query);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getPersons(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $response = Cache::remember('jellyfin_persons_'.md5(json_encode($request->all())), Carbon::now()->addHour(), function () use($request) {
            $api = new JellyfinApiManager();
            return $api->getPersons($request->all());
        });
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getArtists(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $response = Cache::remember('jellyfin_artists_'.md5(json_encode($request->all())), Carbon::now()->addHour(), function () use($request) {
            $api = new JellyfinApiManager();
            return $api->getArtists($request->all());
        });
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getPlugins(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $plugins = $api->getPlugins($request->all());

        $addons = AddonsApiManager::getAddonsFromPlugins();
        $response = array_merge($plugins, $addons);

        return response($response)->header('Content-Type', 'application/json');
    }

    public function getPackages(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $packages = $api->getPackages($request->all());

        $addons = AddonsApiManager::getAddonsFromPackages();
        $response = array_merge($packages, $addons);

        return response($response)->header('Content-Type', 'application/json');
    }
}
