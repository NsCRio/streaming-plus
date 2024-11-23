<?php

if (!function_exists('_env')) {
    function _env($key, $default = null){
        if(!str_starts_with($key, 'SP_')) {
            $key = 'SP_' . $key;
        }
        return \Illuminate\Support\Env::get($key, $default);
    }
}
