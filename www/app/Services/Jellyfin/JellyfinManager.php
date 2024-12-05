<?php

namespace App\Services\Jellyfin;

use LaLit\Array2XML;

class JellyfinManager
{

    /**
     * @throws \Exception
     */
    public static function createStructure(string $directory, array $imdbData){
        self::createNfoFile($directory, $imdbData);
        if(!empty($imdbData['seasons'])){
            self::createSeasonsStructure($directory, $imdbData);
        }else{
            self::createStrmFile("", $directory, $imdbData);
        }
    }


    /**
     * @throws \Exception
     */
    protected static function createSeasonsStructure(string $directory, array $imdbData){
        foreach($imdbData['seasons'] as $season => $episodes) {
            $seasonPath = $directory."/Season ".sprintf("%02d", $season);

            if(!file_exists($seasonPath))
                mkdir($seasonPath, 0777, true);

            foreach($episodes as $episode){
                self::createNfoFile($seasonPath, $episode);
                self::createStrmFile("", $seasonPath, $episode);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected static function createNfoFile(string $directory, array $imdbData): ?string {
        $typeMap = ['movie' => 'movie', 'tvSeries' => 'tvshow', 'tvEpisode' => 'episodedetails'];
        if(in_array($imdbData['type'], array_keys($typeMap))){
            try {
                $type = $typeMap[$imdbData['type']];
                $filePath = $directory . "/" . $type . ".nfo";
                if($type == "episodedetails") {
                    $fileName = 'Episode S'.sprintf("%02d", $imdbData['season']).'E'.sprintf("%02d", $imdbData['episode']);
                    $filePath = $directory . "/" . $fileName . ".nfo";
                }

                if (!file_exists($directory))
                    mkdir($directory, 0777, true);

                unset($imdbData['id']);
                unset($imdbData['seasons']);
                unset($imdbData['totalSeasons']);
                unset($imdbData['totalEpisodes']);
                $imdbData['lockdata'] = "false";

                if (file_exists($filePath)) {
                    $xml = simplexml_load_string(file_get_contents($filePath), "SimpleXMLElement", LIBXML_NOCDATA);
                    $imdbData = array_merge($imdbData, json_decode(json_encode($xml), true));
                }

                $xml = Array2XML::createXML($type, $imdbData);
                file_put_contents($filePath, $xml->saveXML());

                return $filePath;
            }catch (\Exception $e){}
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    public static function createStrmFile(string $streamName = "", string $directory, array $imdbData): ?string {
        try {
            $filePath = $directory . "/" . @$imdbData['imdb_id'];
            $filePath .= (!empty($streamName) ? ' - ' . $streamName : "") . ".strm";
            $streamUrl = config('app.url').'/stream?';
            $fileContent = $streamUrl.http_build_query([
                'imdb_id' => @$imdbData['imdb_id'],
                'provider' => (!empty($streamName) ? md5($streamName) : "")
            ]);

            if ($imdbData['type'] == "tvEpisode") {
                $fileName = 'Episode S' . sprintf("%02d", $imdbData['season']) . 'E' . sprintf("%02d", $imdbData['episode']);
                $filePath = $directory . "/" . $fileName;
                $filePath .= (!empty($streamName) ? ' - ' . $streamName : "") . ".strm";

                $fileContent = $streamUrl.http_build_query([
                    'imdbId' => @$imdbData['parent_imdb_id'],
                    'season' => $imdbData['season'],
                    'episode' => $imdbData['episode'],
                    'provider' => (!empty($streamName) ? md5($streamName) : "")
                ]);
            }

            if (!file_exists($filePath))
                file_put_contents($filePath, $fileContent);

            return $filePath;
        }catch (\Exception $e){}
        return null;
    }

}
