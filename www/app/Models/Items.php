<?php

namespace App\Models;

use App\Services\IMDB\IMDBApiManager;
use App\Services\Items\ItemsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Items extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'item_id';
    public $timestamps = true;

    public function getImdbData(){
        $imdbData = Cache::get('imdb_item_' . md5($this->item_imdb_id), []);
        if(isset($this->item_path) && empty($imdbData)){
            $json = sp_data_path($this->item_path.'/'.$this->item_imdb_id.'.json');
            if(file_exists($json))
                $imdbData = json_decode(file_get_contents($json), true);
        }
        if(empty($imdbData)){
            $api = new IMDBApiManager();
            $imdbData = $api->getTitleDetails($this->item_imdb_id);
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

}
