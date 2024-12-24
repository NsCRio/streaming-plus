<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Models\Users;
use App\Services\Addons\AddonsApiManager;
use App\Services\Items\ItemsManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\Tasks\TaskManager;
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

    public function getItems(Request $request) {
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        $itemType = trim($request->get('includeItemTypes'));

        $response = JellyfinManager::getItemsFromSearchTerm($searchTerm, $itemType, null, $request->query());

        return response()->json($response);
    }

    public function getItem(string $itemId, Request $request) {
        $response = JellyfinManager::getItemById($itemId, $request->query());
        return response()->json($response);
    }

    public function postItem(string $itemId, Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->postItem($itemId, $request->post());
        return response()->json($response);
    }

    public function deleteItem(string $itemId, Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getItems(['ids' => $itemId]);
        if(!empty($response['Items'])){
            foreach($response['Items'] as $item){
                $item = Items::query()->where('item_jellyfin_id', $item['Id'])->first();
                $item->removeFromLibrary();
                $api->startLibraryScan();
                Cache::flush();
                sleep(10);
            }
        }
        return response([])->header('Content-Type', 'application/json');
    }

    public function getItemsLatest(Request $request){
        $api = new JellyfinApiManager();
        $response = $api->getItemsLatest($request->query());
        return response()->json($response);
    }

    public function getItemsImages(string $itemId, string $imageId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item->item_image_url)){
            return Cache::remember('item_image_'.md5($itemId.$imageId), Carbon::now()->addDay(), function () use ($item) {
                return response(@file_get_contents($item->item_image_url), 200)->header('Content-Type', 'image/jpeg');
            });
        }
        return redirect(jellyfin_response($request));
    }

    public function postItemsImages(string $itemId, string $imageId, Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->setItemImage($itemId, $imageId, $request->getContent());
        return response($response, 204)->header('Content-Type', 'application/json');
    }

    public function getItemsPlaybackInfo(string $itemId, Request $request)  {
        $api = new JellyfinApiManager();
        $item = JellyfinManager::getItemDetailById($itemId, $request->query());
        if (!empty($item) && isset($item['Path']) && str_ends_with($item['Path'], '.strm')) {
            if(!empty($item['MediaSources'])) {
                $mediaSource = $item['MediaSources'][array_key_first($item['MediaSources'])];
                if(str_contains($mediaSource['Path'], '/stream?') && str_starts_with($mediaSource['Name'], 'tt')){
                    $source = '/stream?imdbId=' . $mediaSource['Name'];

                    $currentSource = @parse_url(@file_get_contents(@$item['Path']));
                    if (isset($currentSource['path']) && isset($currentSource['query']))
                        $source = $currentSource['path'] . '?' . $currentSource['query'];

                    file_put_contents($item['Path'], app_url($source));
                }
            }
        }
        $response = $api->getItemPlaybackInfo($itemId, $request->all());
        return response()->json($response);
    }

    public function postItemsPlaybackInfo(string $itemId, Request $request) {
        $data = $request->post();

        $itemData = ['itemId' => $itemId, 'mediaSourceId' => @$data['MediaSourceId']];
        if(isset($data['MediaSourceId']))
            $itemData = JellyfinManager::decodeItemId($data['MediaSourceId']);

        $item = JellyfinManager::getItemDetailById($itemData['itemId'], $request->query());
        if (!empty($item) && isset($item['Path']) && str_ends_with($item['Path'], '.strm')) {
            if(!empty($item['MediaSources'])) {
                $mediaSource = $item['MediaSources'][array_key_first($item['MediaSources'])];
                if (str_starts_with($mediaSource['Name'], $item['imdbId'])) {
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
            'data' => $data,
            'query' => $request->query(),
            'url' => $request->fullUrl(),
            'itemId' => $itemId,
            'source' => @$source,
            'response' => $response
        ], JSON_PRETTY_PRINT));

        return response()->json($response);
    }

    public function getItemsDownload(string $itemId, Request $request) {
        $file = file_get_contents(jellyfin_url($request->path(), $request->query()));
        return redirect($file, 301);
    }

    public function getItemsThemeMedia(string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return response()->json([
                'SoundtrackSongsResult' => [],
                'ThemeSongsResult' => [],
                'ThemeVideosResult' => []
            ]);
        }
        $api = new JellyfinApiManager();
        $response = $api->getItemThemeMedia($itemId, $request->query());
        return response()->json($response);
    }

    public function getItemsSimilar(string $itemId, Request $request) {
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            return response()->json([
                'Items' => [],
                'StartIndex' => 0,
                'TotalRecordCount' => 0
            ]);
        }
        $api = new JellyfinApiManager();
        $response = $api->getItemSimilar($itemId, $request->query());
        return response()->json($response);
    }

    /*
     * User Routes
     */
    public function getUsersItems(string $userId, Request $request) {
        $query = $request->query();
        $searchTerm = trim(strtolower($request->get('searchTerm')));
        if($request->has('SearchTerm'))
            $searchTerm = trim(strtolower($request->get('SearchTerm')));
        if($request->has('NameStartsWith'))
            $searchTerm = trim(strtolower($request->get('NameStartsWith')));

        $itemType = trim($request->get('includeItemTypes'));

        $response = JellyfinManager::getItemsFromSearchTerm($searchTerm, $itemType, $userId, $query);

        return response()->json($response);
    }

    public function getUsersItem(string $userId, string $itemId, Request $request) {
        $query = array_merge($request->query(), ['userId' => $userId]);
        $response = JellyfinManager::getItemById($itemId, $query);
        return response()->json($response);
    }

    public function getUsersItemsLatest(string $userId, Request $request) {
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
        return response()->json($response);
    }

    public function getUsersItemPlaybackInfo(string $userId, string $itemId, Request $request) {
        return $this->getItemsPlaybackInfo($itemId, $request);
    }

    public function postUsersItemFavorite(string $userId, string $itemId, Request $request) {
        $api = new JellyfinApiManager();
        $api->setAuthenticationByApiKey();
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            $item->saveItemToLibrary();
            $api->startLibraryScan();

            return response()->json(['IsFavorite' => true]);
        }
        $response = $api->setItemFavorite($itemId, $userId);
        return response()->json($response);
    }

    public function deleteUsersItemFavorite(string $userId, string $itemId, Request $request) {
        $api = new JellyfinApiManager();
        $item = Items::where('item_md5', $itemId)->first();
        if(isset($item)){
            $result = $item->removeFromLibrary();
            $api->startLibraryScan();
            return response()->json(['IsFavorite' => !$result]);
        }
        $response = $api->removeItemFavorite($itemId, $userId);
        return response()->json($response);
    }


    /*
     * Library Routes
     */

    public function getPersons(Request $request) {
        $response = Cache::remember('jellyfin_persons_'.md5(json_encode($request->all())), Carbon::now()->addHour(), function () use($request) {
            $api = new JellyfinApiManager();
            return $api->getPersons($request->all());
        });
        return response()->json($response);
    }

    public function getArtists(Request $request) {
        $response = Cache::remember('jellyfin_artists_'.md5(json_encode($request->all())), Carbon::now()->addHour(), function () use($request) {
            $api = new JellyfinApiManager();
            return $api->getArtists($request->all());
        });
        return response()->json($response);
    }

    public function getPlugins(Request $request) {
        $api = new JellyfinApiManager();
        $plugins = $api->getPlugins($request->all());

        $addons = AddonsApiManager::getAddonsFromPlugins();
        $response = array_merge($plugins, $addons);

        return response()->json($response);
    }

    public function getPackages(Request $request) {
        $api = new JellyfinApiManager();
        $packages = $api->getPackages($request->all());

        $addons = AddonsApiManager::getAddonsFromPackages();
        $response = array_merge($packages, $addons);

        return response()->json($response);
    }


    /**
     * Auth Keys
     */

    public function getAuthKeys(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getApiKeys();
        $response['Items'] = array_filter(array_map(function ($item) {
            return $item['AppName'] !== config('app.code_name') ? $item : null;
        }, $response['Items']));
        return response()->json($response);
    }

    public function postAuthKeys(Request $request) {
        $api = new JellyfinApiManager();
        $response = [];
        if($request->get('App') !== config('app.code_name'))
            $response = $api->createApiKey($request->get('App'));
        return response()->json($response);
    }

    public function deleteAuthKey(string $accessToken, Request $request) {
        $api = new JellyfinApiManager();
        $apiKey = collect(@$api->getApiKeys()['Items'])->where('AccessToken', $accessToken)->first();
        $response = [];
        if(isset($apiKey) && $apiKey['AppName'] !== config('app.code_name'))
            $response = $api->deleteApiKey($accessToken);
        return response()->json($response);
    }

    /**
     * Schedule task route
     */

    public function getScheduledTasks(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getScheduledTasks();

        $tasks = TaskManager::getTaskList();
        $response = array_merge($response, array_values($tasks));

        return response()->json($response);
    }

    public function getScheduledTask(string $taskId, Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getScheduledTask($taskId);
        return response()->json($response);
    }

    public function postScheduledTaskRunning(string $taskId, Request $request) {
        $api = new JellyfinApiManager();

        $task = new TaskManager($taskId);
        if($task->exists()){
            $response = $task->executeTask();
        }else{
            $response = $api->postScheduledTaskRunning($taskId);
        }

        return response()->json($response);
    }

    /*
     * Other Routes
     */

    public function getSystemInfo(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getSystemInfo();
        return response()->json($response);
    }

    public function getSystemInfoPublic(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getSystemInfoPublic();
        $response['LocalAddress'] = config('jellyfin.external_url');
        return response()->json($response);
    }

    public function getSystemConfigurationNetwork(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->getSystemConfiguration('network');
        $response['InternalHttpPort'] = 8096;
        $response['InternalHttpsPort'] = 8920;
        $response['PublicHttpPort'] = 8096;
        $response['PublicHttpsPort'] = 8920;
        return response()->json($response);
    }

    public function postSystemConfigurationNetwork(Request $request) {
        $api = new JellyfinApiManager();
        $data = $api->getSystemConfiguration('network');
        $response = $api->postSystemConfiguration('network', $data);
        return response()->json($response);
    }

    public function getStartupUser(Request $request) {
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
        return response()->json($response);
    }

    public function postStartupUser(Request $request) {
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
        return response()->json($response);
    }

    public function getVirtualFolders(Request $request) {
        $api = new JellyfinApiManager();

        foreach (config('jellyfin.virtual_folders') as $virtualFolder){
            if(!file_exists($virtualFolder['path']))
                mkdir($virtualFolder['path'], 0777, true);

            system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".$virtualFolder['path']);
            $api->createVirtualFolderIfNotExist($virtualFolder['name'], $virtualFolder['path'], $virtualFolder['type']);
        }

        system("chown -R ".env('USER_NAME').":".env('USER_NAME')." ".sp_data_path('/jellyfin'));

        $response = $api->getVirtualFolders();
        return response()->json($response);
    }

    public function postVirtualFolders(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->createVirtualFolder($request->query(), $request->all());
        return response()->json($response, 200, [], );
    }

    public function deleteVirtualFolders(Request $request) {
        $api = new JellyfinApiManager();
        $response = $api->deleteVirtualFolderIfNotPrimary($request->get('name'));
        return response()->json($response);
    }
}
