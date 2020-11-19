<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationsLog extends Model
{
    protected $fillable = [
        'notification_id',
        'status',
        'message'
    ];
    protected  $table='notification_log';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
