<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addons extends Model
{
    protected $table = 'addons';
    protected $primaryKey = 'addon_id';
    public $timestamps = true;

}
