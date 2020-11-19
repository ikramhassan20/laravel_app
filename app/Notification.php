<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';
    protected $fillable = [
        'email',
        'device_token',
        'payload',
        'message',
        'platform',
        'sent',
        'sent_at',
        'viewed',
        'viewed_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function log()
    {
        return $this->hasOne(NotificationsLog::class);
    }
}
