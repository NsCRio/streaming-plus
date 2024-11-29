<?php

namespace App\Models;

use App\Models\lib\AbstractModel;

class Movies extends AbstractModel
{
    protected $table = 'movies';
    protected $primaryKey = 'movie_id';
    protected $fieldPrefix = 'movie';
    public $timestamps = true;

}
