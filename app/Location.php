<?php

namespace App;

use App\Concerns\AppGroupsConcern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use AppGroupsConcern, SoftDeletes;
    protected  $table='location';
    protected $fillable = [
        'app_group_id',
        'name',
        'code',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];
    public function getlocationArea()
    {
        return $this->hasMany(LocationArea::class,'location_id','id');
    }

}
