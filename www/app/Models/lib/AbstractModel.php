<?php

namespace App\Models\lib;

use Illuminate\Database\Eloquent\Model;

class AbstractModel extends Model
{

    public function scopeWhereField($query, $key, $value)
    {
        return $query->where($this->fieldPrefix.'_'.$key, $value);
    }

    public function setField($key, $value){
        $this->attributes[$this->fieldPrefix.'_'.$key] = $value;
    }

    public function getField($key, $value){
        return @$this->attributes[$this->fieldPrefix.'_'.$key];
    }


}
