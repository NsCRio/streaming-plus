<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Models\Users;
use App\Services\Addons\AddonsApiManager;
use App\Services\Items\ItemsManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class JellyfinController extends Controller
{
    /*
     * Items Routes
     */

    public function getItems(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        $itemType = trim($request->get('includeItemTypes'));

        $response = JellyfinManager::getItemsFromSearchTerm($searchTerm, $itemType, null, $request->query());

        return response($response)->header('Content-Type', 'application/json');
    }

    public function getItem(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $response = JellyfinManager::getItemById($itemId, $request->query());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function postItem(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->postItem($itemId, $request->post());
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
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item->item_image_url)){
            return Cache::remember('item_image_'.md5($itemId.$imageId), Carbon::now()->addDay(), function () use ($item) {
                return response(@file_get_contents($item->item_image_url), 200)->header('Content-Type', 'image/jpeg');
            });
        }
        return response(@file_get_contents(jellyfin_url($request->path(), $request->query())), 200)->header('Content-Type', 'image/webp');
    }


    public function getItemsPlaybackInfo(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->getItemPlaybackInfo($itemId, $request->all());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function postItemsPlaybackInfo(string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $data = $request->post();

        $itemData = ['itemId' => $itemId, 'mediaSourceId' => @$data['MediaSourceId']];
        if(isset($data['MediaSourceId']))
            $itemData = JellyfinManager::decodeItemId($data['MediaSourceId']);

        $item = JellyfinManager::getItemDetailById($itemData['itemId'], $request->query());
        if (!empty($item) && isset($item['Path']) && str_ends_with($item['Path'], '.strm')) {
            if(!empty($item['MediaSources'])) {
                $mediaSource = $item['MediaSources'][array_key_first($item['MediaSources'])];
                if ($mediaSource['Name'] == $item['imdbId']) {
                    $source = '/stream?imdbId=' . $item['imdbId'];

                    $currentSource = @parse_url(@file_get_contents(@$item['Path']));
                    if (isset($currentSource['path']) && isset($currentSource['query']))
                        $source = $currentSource['path'] . '?' . $currentSource['query'];

                    if (isset($itemData['streamId']))
                        $source = '/stream?streamId=' . $itemData['streamId'];

                    file_put_contents($item['Path'], app_url($source));
                }
                $data['MediaSourceId'] = $item['Id'];
                $data['AllowVideoStreamCopy'] = true;
                $data['EnableDirectPlay'] = true;
                $data['EnableDirectStream'] = true;
            }
        }

        $api = new JellyfinApiManager();
        $response = $api->postItemPlaybackInfo($itemId, $request->query(), $data);

        Log::info("Stream required: \n" . json_encode([
            'url' => $request->fullUrl(),
            'itemId' => $itemId,
            'source' => @$source
        ], JSON_PRETTY_PRINT));

        return response($response, 200)->header('Content-Type', 'application/json');
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
    public function getUsersItems(string $userId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        if($request->has('SearchTerm'))
            $searchTerm = trim(strtolower($request->get('SearchTerm')));
        if($request->has('NameStartsWith'))
            $searchTerm = trim(strtolower($request->get('NameStartsWith')));

        $itemType = trim($request->get('includeItemTypes'));

        $response = JellyfinManager::getItemsFromSearchTerm($searchTerm, $itemType, $userId, $request->query());

        return response($response)->header('Content-Type', 'application/json');
    }

    public function getUsersItem(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $query = array_merge($request->query(), ['userId' => $userId]);
        $response = JellyfinManager::getItemById($itemId, $query);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getUsersItemsLatest(string $userId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $query = $request->query();
        $api = new JellyfinApiManager();
        $api->setAuthenticationByApiKey();
        $response = $api->getUsersItemsLatest($userId, $query);

        $virtualFolders = $api->getVirtualFolders();
        if(isset($virtualFolders)){
            foreach ($virtualFolders as $folder) {
                $vFolder = collect(config('jellyfin.virtual_folders'))
                    ->whereIn('path', array_values($folder['Locations']))->first();
                if(isset($vFolder) && $query['ParentId'] == $folder['ItemId']){
                    $typesMap = ['movies' => 'movie', 'tvshows' => 'series'];
                    $type = $typesMap[$folder['CollectionType']];
                    $response = JellyfinManager::getDashboardTopItems($type, $query);
                }
            }
        }

        return response($response)->header('Content-Type', 'application/json');
    }

    public function getUsersItemPlaybackInfo(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory|\Illuminate\Http\RedirectResponse {
        return $this->getItemsPlaybackInfo($itemId, $request);
    }

    public function postUsersItemFavorite(string $userId, string $itemId, Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $api->setAuthenticationByApiKey();
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
     * Library Routes
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


    /*
     * Other Routes
     */

    public function getSystemInfo(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->getSystemInfo();
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getSystemInfoPublic(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->getSystemInfoPublic();
        $response['LocalAddress'] = config('jellyfin.external_url');
        $response['ProductName'] = config('app.name')." Server";
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getSystemConfigurationNetwork(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->getSystemConfiguration('network');
        $response['InternalHttpPort'] = 8096;
        $response['InternalHttpsPort'] = 8920;
        $response['PublicHttpPort'] = 8096;
        $response['PublicHttpsPort'] = 8920;
        return response($response)->header('Content-Type', 'application/json');
    }

    public function postSystemConfigurationNetwork(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $data = $api->getSystemConfiguration('network');
        $response = $api->postSystemConfiguration('network', $data);
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getStartupUser(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $info = $api->getSystemInfo();
        $response = $api->getStartupUser();
        $user = Users::query()->where('user_jellyfin_server_id', $info['Id'])->first();
        if(isset($user)){
            $response = [
                'Name' => $user->user_jellyfin_username,
                'Password' => $user->user_jellyfin_password,
            ];
        }
        return response($response)->header('Content-Type', 'application/json');
    }

    public function postStartupUser(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $info = $api->getSystemInfo();

        $user = Users::query()->where('user_jellyfin_username', $request->get('Name'))
            ->where('user_jellyfin_server_id', $info['Id'])->first();
        if(!isset($user)){
            $user = new Users();
            $user->user_jellyfin_username = $request->get('Name');
            $user->user_jellyfin_server_id = $info['Id'];
        }
        $user->user_jellyfin_password = $request->get('Password');
        $user->save();

        $response = $api->postStartupUsers($request->all());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function getVirtualFolders(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();

        foreach (config('jellyfin.virtual_folders') as $virtualFolder){
            if(!file_exists($virtualFolder['path']))
                mkdir($virtualFolder['path'], 0777, true);

            system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".$virtualFolder['path']);
            $api->createVirtualFolderIfNotExist($virtualFolder['name'], $virtualFolder['path'], $virtualFolder['type']);
        }

        system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".sp_data_path('/jellyfin'));

        $response = $api->getVirtualFolders();
        return response($response)->header('Content-Type', 'application/json');
    }

    public function postVirtualFolders(Request $request): \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->createVirtualFolder($request->query(), $request->all());
        return response($response)->header('Content-Type', 'application/json');
    }

    public function deleteVirtualFolders(Request $request) : \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory {
        $api = new JellyfinApiManager();
        $response = $api->deleteVirtualFolderIfNotPrimary($request->get('name'));
        return response($response)->header('Content-Type', 'application/json');
    }
}
