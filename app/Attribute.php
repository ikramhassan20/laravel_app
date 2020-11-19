<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = "attribute";

    protected $fillable = [
        "app_group_id",
        "code",
        "level_type",
        "name",
        "alias",
        "data_type",
        "length",
        "source_table_name",
        "value_column",
        "text_column",
        "where_condition",
        "attribute_type",
        "created_by",
        "updated_by",
        "deleted_at"
    ];

    protected $hidden = ["id", "created_at", "updated_at"];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}
