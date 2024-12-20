<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    #/jellyfin/web/#/wizardstart.html
    dd(config('database.connections.mysql'));
    return $router->app->version();
});

$router->get('/jellyfin', function () use ($router) {
    dd('se sono qui non va bene');
    return $router->app->version();
});

$router->get('/stream', ['as'=> 'stream', 'uses'=>'StreamController@getStream']);

//Jellyfin Proxed Routes
$router->get('/System/Info',                                    ['as'=> 'jellyfin.system.info', 'uses'=>'JellyfinController@getSystemInfoPublic']);
$router->get('/System/Info/Public',                             ['as'=> 'jellyfin.system.info.public', 'uses'=>'JellyfinController@getSystemInfoPublic']);
$router->get('/system/info/public',                             ['as'=> 'jellyfin.system.info.public2', 'uses'=>'JellyfinController@getSystemInfoPublic']);

$router->get('/System/Configuration/network',                   ['as'=> 'jellyfin.system.configuration.network', 'uses'=>'JellyfinController@getSystemConfigurationNetwork']);
$router->post('/System/Configuration/network',                  ['as'=> 'jellyfin.system.configuration.network.post', 'uses'=>'JellyfinController@postSystemConfigurationNetwork']);

$router->get('/Startup/User',                                   ['as'=> 'jellyfin.startup_user', 'uses'=>'JellyfinController@getStartupUser']);
$router->post('/Startup/User',                                  ['as'=> 'jellyfin.startup_user', 'uses'=>'JellyfinController@postStartupUser']);

$router->get('/Library/VirtualFolders',                         ['as'=> 'jellyfin.virtual_folders', 'uses'=>'JellyfinController@getVirtualFolders']);
$router->post('/Library/VirtualFolders',                        ['as'=> 'jellyfin.virtual_folders.create', 'uses'=>'JellyfinController@postVirtualFolders']);
$router->delete('/Library/VirtualFolders',                      ['as'=> 'jellyfin.virtual_folders.delete', 'uses'=>'JellyfinController@deleteVirtualFolders']);

$router->get('/Items',                                          ['as'=> 'jellyfin.items', 'uses'=>'JellyfinController@getItems']);
$router->get('/Items/{itemId}',                                 ['as'=> 'jellyfin.items.detail', 'uses'=>'JellyfinController@getItem']);
$router->post('/Items/{itemId}',                                ['as'=> 'jellyfin.items.post', 'uses'=>'JellyfinController@postItem']);
$router->delete('/Items/{itemId}',                              ['as'=> 'jellyfin.items.delete', 'uses'=>'JellyfinController@deleteItem']);
$router->get('/Items/{itemId}/ThemeMedia',                      ['as'=> 'jellyfin.items.theme_media', 'uses'=>'JellyfinController@getItemsThemeMedia']);
$router->get('/Items/{itemId}/Similar',                         ['as'=> 'jellyfin.items.similar', 'uses'=>'JellyfinController@getItemsSimilar']);
$router->get('/Items/{itemId}/PlaybackInfo',                    ['as'=> 'jellyfin.items.playback_info', 'uses'=>'JellyfinController@getItemsPlaybackInfo']);
$router->post('/Items/{itemId}/PlaybackInfo',                   ['as'=> 'jellyfin.items.playback_info.post', 'uses'=>'JellyfinController@postItemsPlaybackInfo']);
$router->get('/Items/{itemId}/Images/{imageId}',                ['as'=> 'jellyfin.items.images', 'uses'=>'JellyfinController@getItemsImages']);
$router->post('/Items/{itemId}/Images/{imageId}',               ['as'=> 'jellyfin.items.images.post', 'uses'=>'JellyfinController@postItemsImages']);

$router->get('/Users/{userId}/Items',                           ['as'=> 'jellyfin.users.items', 'uses'=>'JellyfinController@getUsersItems']);
$router->get('/Users/{userId}/Items/Latest',                    ['as'=> 'jellyfin.users.items.latest', 'uses'=>'JellyfinController@getUsersItemsLatest']);
$router->get('/Users/{userId}/Items/{itemId}',                  ['as'=> 'jellyfin.users.item', 'uses'=>'JellyfinController@getUsersItem']);
$router->get('/Users/{userId}/Items/{itemId}/PlaybackInfo',     ['as'=> 'jellyfin.users.item.playback_info', 'uses'=>'JellyfinController@getUsersItemPlaybackInfo']);
$router->post('/Users/{userId}/FavoriteItems/{itemId}',         ['as'=> 'jellyfin.users.item.favorite', 'uses'=>'JellyfinController@postUsersItemFavorite']);
$router->delete('/Users/{userId}/FavoriteItems/{itemId}',       ['as'=> 'jellyfin.users.item.favorite.delete', 'uses'=>'JellyfinController@deleteUsersItemFavorite']);

$router->get('/Persons',                                        ['as'=> 'jellyfin.persons', 'uses'=>'JellyfinController@getPersons']);
$router->get('/Artists',                                        ['as'=> 'jellyfin.artists', 'uses'=>'JellyfinController@getArtists']);
$router->get('/Plugins',                                        ['as'=> 'jellyfin.plugins', 'uses'=>'JellyfinController@getPlugins']);
$router->get('/Packages',                                       ['as'=> 'jellyfin.plugins', 'uses'=>'JellyfinController@getPackages']);
