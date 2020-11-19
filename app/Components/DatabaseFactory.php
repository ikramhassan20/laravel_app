<?php

namespace App\Components;

class DatabaseFactory
{
    public static function create($class, $data = [], $times = 1)
    {
        \Schema::disableForeignKeyConstraints();
         $model = ($times > 1) ? factory($class)->times($times)->create($data) : factory($class)->create($data);
        \Schema::enableForeignKeyConstraints();

        return $model;
    }
}