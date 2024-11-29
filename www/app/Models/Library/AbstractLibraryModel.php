<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class AbstractLibraryModel extends Model
{
    protected $connection = 'library';
    protected $primaryKey = 'Id';
    public $timestamps = false;
}
