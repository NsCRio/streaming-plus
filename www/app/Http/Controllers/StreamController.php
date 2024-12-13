<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Lumen\Routing\Controller as BaseController;

class StreamController extends BaseController
{
    public function getStream(Request $request){
        //return Cache::remember('stream_'.md5(json_encode($request->query())), Carbon::now()->addMinutes(10), function() use ($request) {
            if ($request->has('streamId')) {
                $stream = Streams::query()->where('stream_md5', $request->get('streamId'))
                    ->orWhere('stream_jellyfin_id', $request->get('streamId'))->first();
                if (isset($stream)) {
                    $headers = get_headers($stream->stream_url);
                    return redirect($stream->stream_url, 308, $headers);
                }
            }
            if ($request->has('imdbId')) {
                $api = new AddonsApiManager();
                $imdbId = $request->get('imdbId');
                if ($request->has('season'))
                    $imdbId .= ':' . $request->get('season');
                if ($request->has('episode'))
                    $imdbId .= ':' . $request->get('episode');

                $streams = $api->searchStreamByImdbId($imdbId);
                if (!empty($streams)) {
                    $stream = $streams[array_rand($streams)];
                    $headers = get_headers($stream['stream_url']);
                    return redirect($stream['stream_url'], 308, $headers);
                }
            }
            return response('', 404);
        //});
    }
}
