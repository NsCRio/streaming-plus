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
                $path = $item->item_path.'/'.$this->stream_imdb_id.'/'.$this->stream_imdb_id.'.strm';
            }
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
