<?php

namespace App\Services\Jellyfin;

use App\Models\Jellyfin\ApiKeys;
use App\Services\Api\AbstractApiManager;
use App\Services\Jellyfin\lib\Movies;
use App\Services\Jellyfin\lib\TVSeries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class JellyfinApiManager extends AbstractApiManager
{
    protected $endpoint, $headers, $accessToken;

    public function __construct(array $headers = []){
        $this->endpoint = config('jellyfin.url');
        $this->headers = request()->headers->all();

        if(!empty($headers))
            $this->headers = $headers;
    }

    public function getApiKeys(){
        return $this->apiCall('/Auth/Keys', 'GET');
    }

    public function createApiKey(string $apiKeyName){
        $query = ['app' => $apiKeyName];
        return $this->apiCall('/Auth/Keys?'.http_build_query($query), 'POST');
    }

    public function createApiKeyIfNotExists(string $apiKeyName){
        $keys = $this->getApiKeys();
        if(!empty($keys['Items'])){
            $keys = array_filter(array_map(function($key) use($apiKeyName){
                return $key['AppName'] == $apiKeyName ? $key : null;
            }, $keys['Items']));
            if(!empty($keys))
                return $keys[array_key_first($keys)];
        }
        $this->createApiKey($apiKeyName);
        $keys = $this->getApiKeys();
        return collect($keys)->where('AppName', $apiKeyName)->first();
    }

    public function getItemFromQuery(string $itemId, array $query = []): ?array {
        if (isset($query['userId'])) {
            $response = $this->getUsersItem($query['userId'], $itemId, $query);
        } else {
            $response = $this->getItem($itemId, $query);
        }
        return $response;
    }

    public function getItems(array $query = []){
        return $this->apiCall('/Items', 'GET', $query);
    }

    public function getItem(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId, 'GET', $query);
    }

    public function postItem(string $itemId, array $data = []){
        return $this->apiCall('/Items/'.$itemId, 'POST_BODY', $data);
    }

    public function deleteItem(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId, 'DELETE', $query);
    }

    public function getItemImage(string $itemId, string $imageType, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/Images/'.$imageType, 'GET', $query);
    }

    public function getItemsLatest(array $query = []){
        return $this->apiCall('/Items', 'GET', $query);
    }

    public function getUsersItemsLatest(string $userId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items/Latest', 'GET', $query);
    }

    public function getItemPlaybackInfo(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/PlaybackInfo', 'GET', $query);
    }

    public function postItemPlaybackInfo(string $itemId, array $query = [], array $data = []){
        return $this->apiCall('/Items/'.$itemId.'/PlaybackInfo?'.http_build_query($query), 'POST_BODY', $data);
    }

    public function getItemThemeMedia(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/ThemeMedia', 'GET', $query);
    }

    public function getItemSimilar(string $itemId, array $query = []){
        return $this->apiCall('/Items/'.$itemId.'/Similar', 'GET', $query);
    }

    public function getUsersItem(string $userId, string $itemId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items/'.$itemId, 'GET', $query);
    }

    public function getUsersItems(string $userId, array $query = []){
        return $this->apiCall('/Users/'.$userId.'/Items', 'GET', $query);
    }

    public function setItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'POST_JSON', []);
    }

    public function removeItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'DELETE', []);
    }

    public function setSessionsPlaying(array $query = []){
        return $this->apiCall('/Sessions/Playing', 'POST_BODY', $query);
    }

    public function setSessionsPlayingProgress(array $query = []){
        return $this->apiCall('/Sessions/Playing/Progress', 'POST_BODY', $query);
    }

    public function getPersons(array $query = []){
        return $this->apiCall('/Persons', 'GET', $query);
    }

    public function getArtists(array $query = []){
        return $this->apiCall('/Artists', 'GET', $query);
    }

    public function getPlugins(array $query = []){
        return $this->apiCall('/Plugins', 'GET', $query);
    }

    public function getPackages(array $query = []){
        return $this->apiCall('/Packages', 'GET', $query);
    }

    public function getPackagesByName(string $name, array $data){
        return $this->apiCall('/Packages/'.$name, 'GET', $data);
    }

    public function getPackagesRepositories(){
        return $this->apiCall('/Repositories');
    }

    public function getConfiguration(){
        return $this->apiCall('/System/Configuration');
    }

    public function updateConfiguration(array $configuration){
        $data = array_merge($this->getConfiguration(), $configuration);
        return $this->apiCall('/System/Configuration', 'POST_BODY', $data);
    }

    public function getSystemInfo(){
        return $this->apiCall('/System/Info');
    }

    public function getBranding(){
        return $this->apiCall('/Branding/Configuration');
    }

    public function updateBranding(array $configuration){
        $data = array_merge($this->getBranding(), $configuration);
        return $this->apiCall('/System/Configuration/branding', 'POST_BODY', $data);
    }

    public function getVirtualFolders(){
        return $this->apiCall('/Library/VirtualFolders');
    }

    public function addVirtualFolder(string $folderName, string $folderPath, string $collectionType, array $data = []){
        $query = [
            'name' => $folderName,
            'collectionType' => $collectionType,
        ];
        if(empty($data)){
            $data = ($collectionType == "movies") ? Movies::$FOLDER_CONFIG : TVSeries::$FOLDER_CONFIG;
            $data['collectionType'] = $collectionType;
            $data['name'] = $folderName;
            $data['LibraryOptions']['PathInfos'][0]['Path'] = $folderPath;
        }
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'POST_JSON', $data);
    }

    public function createVirtualFolder(array $query, array $data = []){
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'POST_JSON', $data);
    }

    public function deleteVirtualFolder(string $folderName){
        $query = ['name' => $folderName];
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'DELETE');
    }

    public function createVirtualFolderIfNotExist(string $folderName, string $folderPath, string $collectionType){
        $virtualFolders = $this->getVirtualFolders();
        if(!empty($virtualFolders)){
            $virtualFolders = array_filter(array_map(function($folder) use($folderPath){
                return in_array($folderPath, $folder['Locations']) ? $folder : null;
            }, $virtualFolders));
            if(!empty($virtualFolders))
                return $virtualFolders[array_key_first($virtualFolders)];
        }
        $this->addVirtualFolder($folderName, $folderPath, $collectionType);
        $virtualFolders = $this->getVirtualFolders();
        return collect($virtualFolders)->where('Name', $folderName)
            ->where('CollectionType', $collectionType)->first();
    }

    public function deleteVirtualFolderIfNotPrimary(string $folderName = null){
        if(isset($folderName)) {
            $virtualFolders = $this->getVirtualFolders();
            $virtualFolders = array_filter(array_map(function ($folder) use($folderName){
                return md5($folder['Name']) == md5($folderName) ? $folder : null;
            }, $virtualFolders));

            if(!empty($virtualFolders)){
                $virtualFolder = $virtualFolders[array_key_first($virtualFolders)];

                if(in_array(config('jellyfin.movies_path'), $virtualFolder['Locations']) ||
                    in_array(config('jellyfin.series_path'), $virtualFolder['Locations']))
                    return [];

                return $this->deleteVirtualFolder($virtualFolder['Name']);
            }
        }
        return [];
    }

    public function reportsNewMovieAdded(string $imdbId){
        $query = [
            'imdbId' => $imdbId,
        ];
        return $this->apiCall('/Library/Movies/Added?'.http_build_query($query), 'POST');
    }

    public function startLibraryScan(){
        return $this->apiCall('/Library/Refresh?'.http_build_query(['spCall' => true]), 'POST');
    }

    public function createUserIfNotExist(string $username, string $password){
        $users = $this->getUsers();
        $user = collect($users)->where('Name', $username)->first();
        if(!isset($user)) {
            $data = [
                'Name' => $username,
                'Password' => $password,
            ];
            $user = $this->apiCall('/Users/New', 'POST_BODY', $data);
        }
        return $user;
    }

    public function getUsers(){
        return $this->apiCall('/Users', 'GET');
    }

    public function getStartupUser(){
        return $this->apiCall('/Startup/User?'.http_build_query(['spCall' => true]), 'GET');
    }

    public function postStartupUsers(array $data = []){
        return $this->apiCall('/Startup/User?'.http_build_query(['spCall' => true]), 'POST_JSON', $data);
    }

    public function authenticateUser(string $username, string $password){
        $data = [
            'Username' => $username,
            'Pw' => $password,
        ];
        return $this->apiCall('/Users/AuthenticateByName', 'POST_BODY', $data);
    }

    public function updateUserPolicy(string $userId, array $data){
        return $this->apiCall('/Users/'.$userId.'/Policy', 'POST_JSON', $data);
    }

    public static function selfCall(Request $request, array $data = [], $returnBody = false){
        $uri = $request->path();
        $uri = !str_starts_with('/', $uri) ? '/' . $uri : $uri;
        $url = config('jellyfin.url').$uri.'?'.http_build_query($request->query());
        $data = !empty($data) ? $data : $request->post();
        $response = static::call($url, $request->getMethod(), $data, $request->header(), $returnBody);
        return $response ?? [];
    }

    public function setAuthenticationByApiKey(){
        $apiKey = JellyfinManager::getApiKey();
        if(isset($apiKey)){
            if(isset($this->headers['Authorization']))
                unset($this->headers['Authorization']);
            if(isset($this->headers['authorization']))
                unset($this->headers['authorization']);
            if(isset($this->headers['x-emby-token']))
                unset($this->headers['x-emby-token']);
            if(isset($this->headers['X-Emby-Token']))
                unset($this->headers['X-Emby-Token']);

            $this->headers['X-Emby-Token'] = $apiKey;
        }
    }

    protected function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = [], $returnBody = false) : array|null {
        $default_headers = [
            'Content-Type' => 'application/json'
        ];

        if(!empty($this->headers))
            $default_headers = $this->headers;

        $headers = array_merge($default_headers, $headers);
        return parent::apiCall($uri, $method, $data, $headers);
    }

}
