<?php

namespace App\Services\Jellyfin;

use App\Models\Jellyfin\ApiKeys;
use App\Services\Api\AbstractApiManager;
use App\Services\Jellyfin\lib\Movies;
use App\Services\Jellyfin\lib\TVSeries;
use Carbon\Carbon;
use Illuminate\Support\Str;

class JellyfinApiManager extends AbstractApiManager
{
    protected $endpoint, $accessToken;

    public function __construct(string $accessToken = null){
        $this->endpoint = "http://localhost:8097";

        if(is_null($accessToken))
            $accessToken = $this->getAccessToken();

        $this->accessToken = $accessToken;
    }

    public function getItems(array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items', 'GET', $query);
    }

    public function getItem(string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId, 'GET', $query);
    }

    public function deleteItem(string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId, 'DELETE', $query);
    }

    public function getItemImage(string $itemId, string $imageType, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId.'/Images/'.$imageType, 'GET', $query);
    }

    public function getItemPlaybackInfo(string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId.'/PlaybackInfo', 'GET', $query);
    }

    public function getItemThemeMedia(string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId.'/ThemeMedia', 'GET', $query);
    }

    public function getItemSimilar(string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Items/'.$itemId.'/Similar', 'GET', $query);
    }

    public function getUsersItems(string $userId, string $itemId, array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Users/'.$userId.'/Items/'.$itemId, 'GET', $query);
    }

    public function setItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'POST_JSON', []);
    }

    public function removeItemFavorite(string $itemId, string $userId){
        $query = ['userId' => $userId, 'spCall' => true];
        return $this->apiCall('/UserFavoriteItems/'.$itemId.'?'.http_build_query($query), 'DELETE', []);
    }

    public function getPersons(array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Persons', 'GET', $query);
    }

    public function getArtists(array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Artists', 'GET', $query);
    }

    public function getPlugins(array $query = []){
        $query = array_merge($query, ['spCall' => true]);
        return $this->apiCall('/Plugins', 'GET', $query);
    }

    public function getPackages(array $query = []){
        $query = array_merge($query, ['spCall' => true]);
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

    public function addVirtualFolder(string $folderName, string $collectionType){
        $query = [
            'name' => $folderName,
            'collectionType' => $collectionType,
            'refreshLibrary' => "true"
        ];
        $data = ($collectionType == "movies") ? Movies::$FOLDER_CONFIG : TVSeries::$FOLDER_CONFIG;
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'POST_BODY', $data);
    }

    public function removeVirtualFolder(string $folderName, bool $refreshLibrary = true){
        $query = [
            'name' => $folderName,
            'refreshLibrary' => $refreshLibrary
        ];
        return $this->apiCall('/Library/VirtualFolders?'.http_build_query($query), 'DELETE');
    }

    public function createVirtualFolderIfNotExist(string $folderName, string $collectionType){
        $virtualFolders = $this->getVirtualFolders();
        if(!empty($virtualFolders)){
            $virtualFolder = collect($virtualFolders)
                ->where('Name', $folderName)
                ->where('CollectionType', $collectionType)
                ->first();
            if(isset($virtualFolder))
                return $virtualFolder;
        }
        $this->addVirtualFolder($folderName, $collectionType);
        $virtualFolders = $this->getVirtualFolders();
        return collect($virtualFolders)->where('Name', $folderName)
            ->where('CollectionType', $collectionType)->first();
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

    public function getAccessToken(){
        try {
            $apikey = ApiKeys::query()->where('Name', 'streaming-plus')
                ->orderBy('DateCreated')->first();
            if (!isset($apikey)){
                $apikey = new ApiKeys();
                $apikey->DateCreated = Carbon::now()->format('Y-m-d H:i:s.u') . '0';
                $apikey->DateLastActivity = Carbon::now()->format('Y-m-d H:i:s');
                $apikey->Name = "streaming-plus";
                $apikey->AccessToken = md5("streaming-plus-".Str::random());
                $apikey->save();
            }
            return $apikey->AccessToken;
        }catch (\Exception $e){}
        return null;
    }

    protected function apiCall(string $uri, string $method = 'GET', array $data = [], array $headers = [], $returnBody = false) : array|null {
        $default_headers = [
            'Content-Type' => 'application/json',
            'X-Emby-Token' => $this->accessToken
        ];
        $headers = array_merge($default_headers, $headers);
        return parent::apiCall($uri, $method, $data, $headers);
    }

}
