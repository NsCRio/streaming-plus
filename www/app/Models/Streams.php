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


    public function getJellyfinMediaSource(){
        $mediaSource = MediaSource::$CONFIG;
        $mediaSource['Id'] = $this->stream_md5;
        $mediaSource['ETag'] = $this->stream_md5;
        $mediaSource['Path'] = $this->stream_url;
        $mediaSource['Name'] = $this->stream_title;
        return $mediaSource;
    }

}
