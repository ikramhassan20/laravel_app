<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailList extends Model
{
    const STATUS_BLACKLIST = 'blacklist';
    const STATUS_WHITELIST = 'whitelist';

    protected $table = 'email_list';

    protected $fillable = [
        'app_group_id',
        'email',
        'rec_type',
    ];
}
