<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ResponseLog extends Model
{
    /** @var string  */
    protected $table = "response_logs";

    /** @var array  */
    protected $fillable = ['company_id', 'record_id', 'name', 'type', 'console_type', 'response_time', 'created_at', 'updated_at'];


    /**
     * @param $data
     */
    public static function logData($data)
    {
        self::create($data);
    }

}