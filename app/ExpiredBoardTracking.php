<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpiredBoardTracking extends Model
{
    protected $table = 'expired_board_tracking';

    protected $fillable = [
        'board_id', 'row_id', 'app_user_token_id', 'variant_step_id', 'language_id', 'email', 'firebase_key', 'device_key', 'device_type', 'payload', 'message', 'track_key',
        'job', 'status', 'sent', 'viewed', 'started_at', 'ended_at', 'sent_at', 'viewed_at', 'created_at', 'updated_at'
    ];
}
