<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinManager;
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

        if ($request->has('imdbId')) {
            $streams = Streams::query()->where('stream_imdb', $request->get('imdbId'))
                ->where('created_at', '<=', Carbon::now()->addHour())->get();
            if($streams->count() <= 0)
                $streams = collect(StreamsManager::searchStreamsFromAddons($request->get('imdbId')));

            if (!empty($streams)) {
                $addonsIds = AddonsApiManager::getActiveAddonsIds();
                $streams->whereIn('stream_addon_id', $addonsIds);
                $streams->sortBy('stream_protocol');
                $streams->sortBy('stream_title', SORT_NATURAL);
                $streams->sortBy('updated_at', SORT_DESC);
                $stream = $streams->first();
            }
        }

        if ($request->has('streamId'))
            $stream = Streams::query()->where('stream_md5', $request->get('streamId'))->first();

        if (isset($stream)) {
            $streamUrl = $stream->stream_url;
            if ($stream->stream_protocol == "torrent")
                $streamUrl = app_url('/stream-torrent/' . $stream->stream_url);

            $path = $stream->getItemPath();
            if (isset($path))
                file_put_contents($path, app_url('/stream?streamId=' . $stream->stream_md5));

            $stream->stream_watched_at = Carbon::now();
            $stream->save();

            Log::info('Playing stream "' . $stream->stream_title . '" from ' . $streamUrl);

            return redirect($streamUrl, 301);
        }

        return response(null, 404);
    }
}
