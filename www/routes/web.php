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
$router->get('/Items',                                          ['as'=> 'jellyfin.items', 'uses'=>'JellyfinController@getItems']);
$router->get('/Items/{itemId}',                                 ['as'=> 'jellyfin.items.detail', 'uses'=>'JellyfinController@getItem']);
$router->delete('/Items/{itemId}',                              ['as'=> 'jellyfin.items.delete', 'uses'=>'JellyfinController@deleteItem']);
$router->get('/Items/{itemId}/ThemeMedia',                      ['as'=> 'jellyfin.items.theme_media', 'uses'=>'JellyfinController@getItemsThemeMedia']);
$router->get('/Items/{itemId}/Similar',                         ['as'=> 'jellyfin.items.similar', 'uses'=>'JellyfinController@getItemsSimilar']);
$router->post('/Items/{itemId}/PlaybackInfo',                   ['as'=> 'jellyfin.items.playback_info', 'uses'=>'JellyfinController@getItemsPlaybackInfo']);
$router->get('/Items/{itemId}/Images/{imageId}',                ['as'=> 'jellyfin.items.images', 'uses'=>'JellyfinController@getItemsImages']);

$router->get('/Users/{userId}/Items/{itemId}',                  ['as'=> 'jellyfin.users.items', 'uses'=>'JellyfinController@getUsersItem']);
$router->get('/Users/{userId}/Items/{itemId}/PlaybackInfo',     ['as'=> 'jellyfin.users.items.playback_info', 'uses'=>'JellyfinController@getUsersItemPlaybackInfo']);
$router->post('/Users/{userId}/FavoriteItems/{itemId}',         ['as'=> 'jellyfin.users.items.favorite', 'uses'=>'JellyfinController@postUsersItemFavorite']);
$router->delete('/Users/{userId}/FavoriteItems/{itemId}',       ['as'=> 'jellyfin.users.items.favorite.delete', 'uses'=>'JellyfinController@deleteUsersItemFavorite']);

$router->get('/Persons',                                        ['as'=> 'jellyfin.persons', 'uses'=>'JellyfinController@getPersons']);
$router->get('/Artists',                                        ['as'=> 'jellyfin.artists', 'uses'=>'JellyfinController@getArtists']);
$router->get('/Plugins',                                        ['as'=> 'jellyfin.plugins', 'uses'=>'JellyfinController@getPlugins']);
$router->get('/Packages',                                       ['as'=> 'jellyfin.plugins', 'uses'=>'JellyfinController@getPackages']);
