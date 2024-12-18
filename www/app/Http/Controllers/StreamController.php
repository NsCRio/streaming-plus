<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Streams\StreamsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Laravel\Lumen\Routing\Controller as BaseController;

class StreamController extends BaseController
{
    public function getStream(Request $request){
        if ($request->has('streamId')) {
            $stream = Streams::query()->where('stream_md5', $request->get('streamId'))
                ->orWhere('stream_jellyfin_id', $request->get('streamId'))->first();
            $stream = $stream?->toArray();
        }

        if ($request->has('imdbId')) {
            $streams = Streams::query()->where('stream_imdb', $request->get('imdbId'))->get()->toArray();
            if(!isset($stream))
                $streams = StreamsManager::searchStreamsFromAddons($request->get('imdbId'));

            if (!empty($streams)) {
                $streams = array_filter(array_map(function ($stream) {
                    return $stream['stream_protocol'] == "http" ? $stream : null;
                }, $streams));
                $stream = $streams[array_rand($streams)];
            }
        }

        if (isset($stream)) {
            if($stream['stream_protocol'] == "torrent")
                return redirect(app_url('/stream-torrent/'.$stream['stream_url']), 301);

            return redirect($stream['stream_url'], 301);
        }

        return response('', 404);
    }
}
