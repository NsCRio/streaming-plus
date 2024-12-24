<?php

namespace App\Models;

use App\Services\Jellyfin\JellyfinManager;
use App\Services\Jellyfin\lib\MediaSource;
use Illuminate\Database\Eloquent\Model;

class Streams extends Model
{
    protected $table = 'streams';
    protected $primaryKey = 'stream_id';
    public $timestamps = true;

    public function getStreamUrl(){
        if(isset($this->stream_url)) {
            $streamUrl = $this->stream_url;
            if ($this->stream_protocol == "torrent")
                $streamUrl = app_url('/stream-torrent/' . $this->stream_url);

            if (ping($streamUrl))
                return $streamUrl;
        }
        return false;
    }

    public function getItem(){
        $imdbId = @explode(':', @urldecode($this->stream_imdb_id))[0];
        if(isset($imdbId)){
            return Items::query()->where('item_imdb_id', $imdbId)->first();
        }
        return null;
    }

    public function getItemPath(){
        $item = $this->getItem();
        if(isset($item->item_path)){
            $path = $item->item_path.'/'.$this->stream_imdb_id.'.strm';
            if($item->item_type == "tvSeries"){
                $imdbId = explode(':', $this->stream_imdb_id);
                if(count($imdbId) > 1)
                    $path = $item->item_path.'/'.$imdbId[0].':'.$imdbId[1].'/'.$this->stream_imdb_id.'.strm';
            }
            if(file_exists(sp_data_path($path)))
                return sp_data_path($path);
        }
        return null;
    }

    public function getJellyfinItem(){
        $item = $this->getItem();
        if(!empty($item) && isset($item->item_jellyfin_id)){
            return JellyfinManager::getItemDetailById($item->item_jellyfin_id);
        }
        return null;
    }

    public function getJellyfinMediaSource(){
        $mediaSource = MediaSource::$CONFIG;
        $mediaSource['Id'] = $this->stream_md5;
        $mediaSource['ETag'] = $this->stream_md5;
        $mediaSource['Path'] = $this->stream_url;
        $mediaSource['Name'] = $this->stream_title;
        return $mediaSource;
    }

}
