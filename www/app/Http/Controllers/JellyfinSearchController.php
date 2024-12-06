<?php

namespace App\Http\Controllers;

use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\StreamingPlus\ItemsSearchManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JellyfinSearchController extends Controller
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
            sleep(10);
            if (!$isSearching && !$hasResult && !$isMissing) {
                Cache::put("jellyfin_searching_" . md5($searchTerm), $searchTerm);

                $search = new ItemsSearchManager($searchTerm);
                $results = $search->search()->getResults();

                if (!empty($results)) {
                    $api->startLibraryScan();
                    $ids = array_map(function ($item) {
                        return $item->item_imdb_id;
                    }, $results);

                    sleep(count($ids)*10);
                    Cache::put("jellyfin_search_" . md5($searchTerm), $ids, Carbon::now()->addHour());
                }

                Cache::forget("jellyfin_searching_" . md5($searchTerm));
            }
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
}
