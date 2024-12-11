<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JellyfinController extends Controller
{

    /*
     * Items Routes
     */

    public function getItems(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $query = $request->all();
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        $itemType = trim($request->get('includeItemTypes'));
        $isMissing = $request->get('isMissing', false);

        $api = new JellyfinApiManager();
        $response = $api->getItems($query);

        if(in_array($itemType, ["Movie", "Series"]) && !$isMissing)
            $response = JellyfinManager::getItemsFromSearchTerm($searchTerm, $itemType, $response);

        return response($response)->header('Content-Type', 'application/json');
    }

    public function getItem(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $response = JellyfinManager::getItemById($itemId, $request->query());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function deleteItem(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->getItems(['ids' => $itemId]);
        if(!empty($response['Items'])){
            foreach($response['Items'] as $item){
                $item = Items::query()->where('item_jellyfin_id', $item['Id'])->first();
                $item->removeFromLibrary();
                $api->startLibraryScan();
                sleep(10);
            }
        }
        return response([])->header('Content-Type', 'application/json');
    }

    public function getItemsImages(string $itemId, string $imageId, Request $request) {
        return Cache::remember('item_image_'.md5($itemId.$imageId), Carbon::now()->addDay(), function () use ($request, $itemId, $imageId) {
            $item = Items::where('item_md5', $itemId)->first();
            if(isset($item->item_image_url)){
                return response(file_get_contents($item->item_image_url), 200)->header('Content-Type', 'image/jpeg');
            }
            return response(file_get_contents(jellyfin_url($request->path(), $request->query())), 200)->header('Content-Type', 'image/webp');
        });
    }

    public function getItemsPlaybackInfo(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return response([
                'MediaSources' => [],
                'PlaySessionId' => md5('test'),
            ])->header('Content-Type', 'application/json');
        }
        $api = new JellyfinApiManager();
        $response = $api->getItemPlaybackInfo($itemId);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getItemsThemeMedia(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return response([
                'SoundtrackSongsResult' => [],
                'ThemeSongsResult' => [],
                'ThemeVideosResult' => []
            ])->header('Content-Type', 'application/json');
        }
        $api = new JellyfinApiManager();
        $response = $api->getItemThemeMedia($itemId, $request->query());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getItemsSimilar(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return response([
                'Items' => [],
                'StartIndex' => 0,
                'TotalRecordCount' => 0
            ])->header('Content-Type', 'application/json');
        }
        $api = new JellyfinApiManager();
        $response = $api->getItemSimilar($itemId, $request->query());
        return response($response)->header('Content-Type', 'application/json');
    }

    /*
     * User Routes
     */
    public function getUsersItem(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $query = array_merge($request->query(), ['userId' => $userId]);
        $response = JellyfinManager::getItemById($itemId, $query);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getUsersItemPlaybackInfo(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory|\Illuminate\Http\RedirectResponse {
        //TODO: retrieve play urls
        $item = JellyfinManager::getItemById($itemId);
        if(isset($item)){
            return response([
                'MediaSources' => [],
                'PlaySessionId' => md5('test'),
            ])->header('Content-Type', 'application/json');
        }
        return redirect(jellyfin_url($request->path(), $request->query()));
    }

    public function postUsersItemFavorite(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            $item->saveItemToLibrary();
            $api->startLibraryScan();

            return response(['IsFavorite' => true])->header('Content-Type', 'application/json');
        }
        $response = $api->setItemFavorite($itemId, $userId);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function deleteUsersItemFavorite(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            $result = $item->removeFromLibrary();
            $api->startLibraryScan();
            return response(['IsFavorite' => !$result])->header('Content-Type', 'application/json');
        }
        $response = $api->removeItemFavorite($itemId, $userId);
        return response($response)->header('Content-Type', 'application/json');
    }


    /*
     * Other Routes
     */

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
