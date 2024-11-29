<?php

if (!function_exists('_env')) {
    function _env($key, $default = null){
        if(!str_starts_with($key, 'SP_')) {
            $key = 'SP_' . $key;
        }
        return \Illuminate\Support\Env::get($key, $default);
    }
}

if (!function_exists('sp_data_path')) {
    function sp_data_path($path){
        if(str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }
        return '/data/' . $path;
        //return realpath('/data/'.$path);
    }
}
