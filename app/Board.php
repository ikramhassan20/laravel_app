<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    //
    protected $table = 'board';

    protected $fillable = [
        'app_group_id',
        'tags',
        'code',
        'name',
        'step',
        'start_time',
        'end_time',
        'status',
        'is_remove_cache',
        'schedule_type',
        'delivery_type',
        'priority',
        'action_trigger_delay_value',
        'action_trigger_delay_unit',
        'delivery_control',
        'delivery_control_delay_value',
        'delivery_control_delay_unit',
        'capping',
        'created_by',
        'updated_by'
    ];

    const STEP_BASIC = 'general';
    const STEP_DELIVERY = 'delivery';
    const STEP_AUDIENCE = 'target';
    const STEP_SETTING = 'setting';
    const STEP_BUILD = 'preview';
    const STEP_LEVEL = ['general', 'delivery', 'target', 'setting', 'preview'];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';

    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_ONCE = 'once';

    const DELIVERY_TYPE_SCHEDULE = 'schedule';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const BOARD_TRACKING_EXECUTING_STATUS = 'executing';
    const BOARD_TRACKING_ADDED_STATUS = 'added';
    const BOARD_TRACKING_COMPLETED_STATUS = 'completed';
    const BOARD_TRACKING_FAILED_STATUS = 'failed';

    const BOARD_INAPP_CODE = 'InApp';
    const BOARD_EMAIL_CODE = 'Email';
    const BOARD_PUSH_CODE = 'Push';

    public function segments()
    {
        return $this->belongsToMany(Segment::class, 'board_segment');
    }

    public function schedules()
    {
        return $this->hasMany(BoardSchedule::class);
    }

    public function board_tracking()
    {
        return $this->hasMany(BoardTracking::class, 'board_id', 'id');
    }
    public function linkTracking()
    {
        return $this->hasMany(LinkTrackings::class, 'rec_id','id');
    }
}
