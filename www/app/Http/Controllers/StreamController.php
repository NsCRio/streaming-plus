<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Streams\StreamsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Lumen\Routing\Controller as BaseController;

class StreamController extends BaseController
{
    public function getStream(Request $request){
        if ($request->has('streamId')) {
            $stream = Streams::query()->where('stream_md5', $request->get('streamId'))
                ->orWhere('stream_jellyfin_id', $request->get('streamId'))->first();
        }

        if (!isset($stream) && $request->has('imdbId')) {
            $streams = Streams::query()->where('stream_imdb', $request->get('imdbId'))->get();
            if($streams->count() <= 0)
                $streams = collect(StreamsManager::searchStreamsFromAddons($request->get('imdbId')));

            if (!empty($streams)) {
                $streams->sortByDesc('stream_protocol');
                $streams->sortByDesc('stream_title');
                $streams->sortByDesc('stream_watched_at');
                $stream = $streams->first();
            }
        }

        if (isset($stream)) {
            $stream->stream_watched_at = Carbon::now();
            $stream->save();

            Log::info('Playing stream "'.$stream->stream_title.'" from '.$stream->stream_url);

            if($stream->stream_protocol == "torrent")
                return redirect(app_url('/stream-torrent/'.$stream->stream_url), 301);

            return redirect($stream->stream_url, 301);
        }

        return response(null, 404);
    }
}
