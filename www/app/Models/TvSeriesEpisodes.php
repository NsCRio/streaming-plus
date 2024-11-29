<?php

namespace App\Models;

use App\Models\lib\AbstractModel;

class TvSeriesEpisodes extends AbstractModel
{
    protected $table = 'tv_series_episodes';
    protected $primaryKey = 'episode_id';
    protected $fieldPrefix = 'episode';
    public $timestamps = true;

}
