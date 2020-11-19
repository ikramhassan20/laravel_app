<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserEmailSettings extends Model
{
    protected $fillable = [
        'company_id',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_name',
        'from_email'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}
