<?php

namespace App\Models;

use App\Services\IMDB\IMDBApiManager;
use App\Services\Items\ItemsManager;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Items extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'item_id';
    public $timestamps = true;


    public function getJellyfinItem(){
        if(isset($this->item_jellyfin_id))
            return JellyfinManager::getItemDetailById($this->item_jellyfin_id);
        return null;
    }

    public function getImdbData(){
        $imdbData = [];
        if(isset($this->item_imdb_id)) {
            if (isset($this->item_path) && empty($imdbData)) {
                $json = sp_data_path($this->item_path . '/' . $this->item_imdb_id . '.json');
                if (file_exists($json))
                    $imdbData = json_decode(file_get_contents($json), true);
            }
            if (empty($imdbData))
                $imdbData = ItemsManager::getImdbData($this->item_imdb_id);
        }
        return $imdbData;
    }

    public function saveItemToLibrary() : null|string {
        $imdbData = $this->getImdbData();
        if(!empty($imdbData)) {
            $this->item_path = ItemsManager::putImdbDataToLocalStorage($imdbData);
            $this->save();
        }
        return $this->item_path;
    }

    public function updateItemToLibrary() : null|string {
        $imdbData = ItemsManager::getImdbData($this->item_imdb_id);
        if(!empty($imdbData)) {
            $this->item_path = ItemsManager::putImdbDataToLocalStorage($imdbData);
            $this->save();
        }
        return $this->item_path;
    }

    public function removeFromLibrary(): bool {
        if(isset($this->item_path)){
            if(file_exists(sp_data_path($this->item_path))) {
                try{
                    remove_dir(sp_data_path($this->item_path));
                    $this->item_path = null;
                    $this->item_jellyfin_id = null;
                    return true;
                }catch (\Exception $e){}
            }
        }
        return false;
    }

    public function getJellyfinDetailItem($withImdbData = true){
        if($withImdbData)
            $imdbData = $this->getImdbData();

        $overview = "------------------------------\n\n";
        $overview .= "⚠️ **How to watch this title**:\n";
        $overview .= "- Click on the ♥ Heart icon to add this item to the library.\n";
        $overview .= "- Make sure you have added at least one addon to the library.\n";
        $overview .= "- Select one link from those available.\n";
        $overview .= "- Enjoy.\n";
        $overview .= "------------------------------\n\n";

        $outcome = \App\Services\Jellyfin\lib\Items::$CONFIG;
        $outcome['CommunityRating'] = @$imdbData['rating'];
        $outcome['DateCreated'] = Carbon::parse($this->created_at)->timestamp;
        $outcome['ProductionYear'] = $this->item_year;
        $outcome['PremiereDate'] = $this->item_year."-01-01T00:00:00.0000000Z";
        $outcome['ExternalUrls'][] = [
            'Name' => 'IMDb',
            'Url' => 'https://www.imdb.com/title/' . $this->item_imdb_id,
        ];
        $outcome['Genres'] = @$imdbData['genre'];
        $outcome['Id'] = $this->item_md5;
        $outcome['ImageTags']['Primary'] = $this->item_image_md5;
        $outcome['Name'] = $this->item_title;
        $outcome['OriginalTitle'] = $this->item_original_title;
        $outcome['Overview'] = $overview . @$imdbData['plot'];
        $outcome['ParentId'] = $this->item_md5;
        $outcome['ProviderIds']['Imdb'] = $this->item_imdb_id;
        $outcome['ServerId'] = $this->item_server_id;
        $outcome['SortName'] = $this->item_title;
        //$outcome['Type'] = $this->item_type == "tvSeries" ? 'Series' : 'Movie';
        $outcome['Type'] = "Unknown";
        $outcome['Path'] = null;
        $outcome['MediaStreams'] = null;
        $outcome['MediaSources'] = [];
        $outcome['VideoType'] = 'Unknown';
        $outcome['MediaType'] = 'Unknown';
        $outcome['LocationType'] = 'Remote';
        $outcome['UserData'] = [];
        return $outcome;
    }

    public function getJellyfinListItem($type = "CollectionFolder"){
        $outcome = \App\Services\Jellyfin\lib\Items::$CONFIG;
        return array_merge($outcome, [
            'Name' => $this->item_title,
            'ServerId' => $this->item_server_id,
            'Id' => $this->item_jellyfin_id ?? $this->item_md5,
            'PremiereDate' => $this->item_year."-01-01T00:00:00.0000000Z",
            'CriticRating' => null,
            'OfficialRating' => null,
            'ChannelId' => null,
            'CommunityRating' => null,
            'ProductionYear' => $this->item_year,
            'IsFolder' => false,
            'Type' => $type,
            //'Type' => 'Unknown',
            //'Type' => $this->item_type == "tvSeries" ? 'Series' : 'Movie',
            'PrimaryImageAspectRatio' => 0.7,
            'UserData' => [
                'PlaybackPositionTicks' => 0,
                'PlayCount' => 0,
                'IsFavorite' => isset($this->item_path),
                'Played' => false,
                'Key' => null,
                'ItemId' => '00000000000000000000000000000000'
            ],
            'VideoType' => 'Unknown',
            //'VideoType' => 'VideoFile',
            'ImageTags' => [
                "Primary" => $this->item_image_md5,
            ],
            //'LocationType' => 'FileSystem',
            'LocationType' => 'Remote',
            'MediaType' => 'Unknown',
            //'MediaType' => 'Video',
        ]);
    }
}
