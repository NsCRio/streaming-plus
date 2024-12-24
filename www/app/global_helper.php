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

if (!function_exists('ping')) {
    function ping($url){
        $response = false;
        try {
            $url = parse_url($url);
            $host = @$url['host'];
            $port = @$url['port'] ?? 80;
            if (isset($host, $port)) {
                $waitTimeoutInSeconds = 1;
                if ($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds))
                    $response = true;
                fclose($fp);
            }
        }catch (\Exception $e){}
        return $response;
    }
}


if (!function_exists('app_url')) {
    function app_url($path = "", array $query = []){
        $url = "http://".env('HTTP_HOST');
        if(!ping($url)){
            $uInfo = parse_url($url);
            if(isset($uInfo['scheme'], $uInfo['host']))
                $url = $uInfo['scheme'].'://'.$uInfo['host'];
        }
        if(!ping($url))
            $url = config('app.url');

        if(!empty($path)) {
            $path = !str_starts_with($path, '/') ? '/' . $path : $path;
            $path .= !empty($query) ? '?' . http_build_query($query, '', '&') : '';
        }
        return $url . $path;
    }
}

if (!function_exists('sp_url')) {
    function sp_url($path = "", array $query = []){
        $url = config('app.url');
        $path = !str_starts_with($path, '/') ? '/' . $path : $path;
        $path .= !empty($query) ? '?' . http_build_query($query, '', '&') : '';
        return $url . $path;
    }
}

if (!function_exists('jellyfin_response')) {
    function jellyfin_response($request){
        $url = app_url($request->path());
        $query = array_merge($request->query(), ['spCall' => true]);
        return $url . '?' . http_build_query($query, '', '&');
    }
}



if (!function_exists('jellyfin_url')) {
    function jellyfin_url($path = "", array $query = []){
        $url = config('jellyfin.url');
        $path = !str_starts_with($path, '/') ? '/' . $path : $path;
        $query = array_merge($query, ['spCall' => true]);
        return $url . $path . '?' . http_build_query($query, '', '&');
    }
}

if (!function_exists('get_last_url')) {
    function get_last_url($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch);
        return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    }
}


if (!function_exists('remove_dir')) {
    function remove_dir($path){
        if(file_exists($path)){
            system("rm -rf ".escapeshellarg($path));
        }
    }
}

if (!function_exists('save_image')) {
    function save_image($inPath, $outPath)
    {
        $in = fopen($inPath, "rb");
        $out = fopen($outPath, "wb");
        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }
}
