<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Laravel\Lumen\Routing\Controller as BaseController;

class StreamController extends BaseController
{
    public function getStream(Request $request){
        //return Cache::remember('stream_'.md5(json_encode($request->query())), Carbon::now()->addMinutes(10), function() use ($request) {
            if ($request->has('streamId')) {
                $stream = Streams::query()->where('stream_md5', $request->get('streamId'))
                    ->orWhere('stream_jellyfin_id', $request->get('streamId'))->first();
                $stream = $stream?->toArray();
            }
            if ($request->has('imdbId')) {
                $api = new AddonsApiManager();
                $imdbId = $request->get('imdbId');
                if ($request->has('season'))
                    $imdbId .= ':' . $request->get('season');
                if ($request->has('episode'))
                    $imdbId .= ':' . $request->get('episode');

                $streams = $api->searchStreamByImdbId($imdbId);
                if (!empty($streams))
                    $streams = array_filter(array_map(function ($stream) {
                        return $stream['stream_protocol'] == "http" ? $stream : null;
                    }, $streams));
                    $stream = $streams[array_rand($streams)];
            }

            if (isset($stream)) {
                Session::put('stream', $stream);
                if(!str_starts_with($stream['stream_url'], 'http')) {
                    return redirect(app_url('/stream-torrent/'.$stream['stream_url']), 301);
                }else {
                    $file = pathinfo($stream['stream_url']);
                    if (in_array($file['extension'], ['m3u', 'm3u8'])) {
                        return response(file_get_contents($stream['stream_url']), 200);
                    }
                    return redirect($stream['stream_url'], 301);
                }
            }

            return response('', 404);
        //});
    }
}
