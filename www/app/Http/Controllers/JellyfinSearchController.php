<?php

namespace App\Http\Controllers;

use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\StreamingPlus\ItemsSearchManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JellyfinSearchController extends Controller
{

    public function getItems(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        $itemType = $request->get('includeItemTypes');
        $isMissing = $request->get('isMissing', false);
        $isSearching = Cache::has("jellyfin_searching_".md5($searchTerm));
        $hasResult = Cache::has("jellyfin_search_result_".md5($searchTerm));

        $api = new JellyfinApiManager();

        if(!$isSearching && !$hasResult && !$isMissing && in_array(trim($itemType), ["Movie", "Series"])){
            $searchTerms = array_merge(Cache::get('jellyfin_search', []), [$searchTerm]);
            Cache::put('jellyfin_search', $searchTerms);

            if(array_count_values($searchTerms)[$searchTerm] == 2){ //Movie and Series
                Cache::put("jellyfin_searching_".md5($searchTerm), $searchTerm);

                $search = new ItemsSearchManager($searchTerm);
                $results = $search->search()->getResults();

                if(!empty($results))
                    $api->startLibraryScan();

                sleep(10); //Do il tempo a Jellyfin di aggiornare la libreria
                Cache::put("jellyfin_search_result_".md5($searchTerm), $results, Carbon::now()->addMinutes(10));
                Cache::forget("jellyfin_searching_".md5($searchTerm));
                Cache::forget('jellyfin_search');
            }
        }

        $response = $api->getItems($request->all());

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
}
