<?php

namespace App\Models;

use App\Models\lib\AbstractModel;

class TvSeries extends AbstractModel
{
    protected $table = 'tv_series';
    protected $primaryKey = 'series_id';
    protected $fieldPrefix = 'series';
    public $timestamps = true;

}
