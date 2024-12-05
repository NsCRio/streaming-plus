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
$router->get('/Items', ['as'=> 'jellyfin.items', 'uses'=>'JellyfinSearchController@getItems']);
$router->get('/Persons', ['as'=> 'jellyfin.persons', 'uses'=>'JellyfinSearchController@getPersons']);
$router->get('/Artists', ['as'=> 'jellyfin.artists', 'uses'=>'JellyfinSearchController@getArtists']);
