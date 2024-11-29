<?php

namespace App\Services\Datasets\lib;

class AbstractDataset
{
    private static $name = "";
    private static $code = "";
    private static $db_id = "";

    public static function getName(){
        return static::$name;
    }

    public static function getCode(){
        return static::$name;
    }

    public static function getUrl(){
        return static::$name;
    }

}
