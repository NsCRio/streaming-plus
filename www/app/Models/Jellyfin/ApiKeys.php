<?php

namespace App\Models\Jellyfin;

use App\Models\lib\AbstractModel;

class ApiKeys extends AbstractModel
{
    protected $connection = 'jellyfin';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $table = 'ApiKeys';
}
