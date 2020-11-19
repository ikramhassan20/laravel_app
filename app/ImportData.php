<?php

namespace App;

use App\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Model;

class ImportData extends Model
{
    use CommonTrait;

    protected $table = 'import_data';

    protected $fillable = [
        'company_id', 'actual_file_name', 'file_name', 'file_size', 'file_path', 'is_processed', 'is_deleted', 'process_date', 'created_by', 'status', 'reason', 'remaining_files'
    ];

    protected $appends = [
        'flag'
    ];

    public function getFileSizeAttribute()
    {
        return self::getFileSize($this->attributes['file_size']);
    }

    public function getFlagAttribute()
    {
        if($this->attributes['status'] == "Inprogress") {
            return "In Progress";
        }

        return $this->attributes['status'];
    }
}
