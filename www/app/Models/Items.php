<?php

namespace App\Models;

use App\Models\lib\AbstractModel;

class Items extends AbstractModel
{
    protected $table = 'items';
    protected $primaryKey = 'item_id';
    protected $fieldPrefix = 'item';
    public $timestamps = true;

    public function getImdbData(){
        if(isset($this->item_path)){
            $json = sp_data_path($this->item_path.'/'.$this->item_imdb_id.'.json');
            if(file_exists($json))
                return json_decode(file_get_contents($json), true);
        }
        return [];
    }

}
