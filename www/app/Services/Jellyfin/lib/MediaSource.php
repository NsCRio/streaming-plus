<?php

namespace App\Services\Jellyfin\lib;

class MediaSource
{
    public static $CONFIG = [
          "Protocol" => "Http",
          "Id" => null,
          "Path" => null,
          "Type" => "Default",
          "Container" => "hls",
          "Size" => null,
          "Name" => null,
          "IsRemote" => true,
          "ETag" => null,
          "RunTimeTicks" => null,
          "ReadAtNativeFramerate" => false,
          "IgnoreDts" => false,
          "IgnoreIndex" => false,
          "GenPtsInput" => false,
          "SupportsTranscoding" => true,
          "SupportsDirectStream" => true,
          "SupportsDirectPlay" => true,
          "IsInfiniteStream" => false,
          "UseMostCompatibleTranscodingProfile" => false,
          "RequiresOpening" => false,
          "RequiresClosing" => false,
          "RequiresLooping" => false,
          "SupportsProbing" => true,
          "VideoType" => "VideoFile",
          "MediaStreams" => [],
          "MediaAttachments" => [],
          "Formats" => [],
          "Bitrate" => null,
          "RequiredHttpHeaders" => [],
          "TranscodingSubProtocol" => "http",
          "DefaultAudioStreamIndex" => 1,
          "HasSegments" => false,
    ];
}
