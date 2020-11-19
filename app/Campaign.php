<?php

namespace App;

use App\Concerns\CampaignDetailsConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use CampaignDetailsConcerns, SoftDeletes;

    protected $table = "campaign";

    const STEP_GENERAL = 'general';
    const STEP_COMPOSE = 'compose';
    const STEP_DELIVERY = 'delivery';
    const STEP_TARGET = 'target';
    const STEP_CONVERSION = 'conversion';
    const STEP_PREVIEW = 'preview';
    const STEP_LEVEL = ['general', 'compose', 'delivery', 'target', 'conversion', 'preview'];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';

    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_ONCE = 'once';

    const DELIVERY_TYPE_SCHEDULE = 'schedule';
    const DELIVERY_TYPE_ACTION = 'action';
    const DELIVERY_TYPE_API = 'api';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    const CAMPAIGN_LOOKUP_CODE = 'campaign_type';
    const CAMPAIGN_INAPP_CODE = 'inapp';
    const CAMPAIGN_EMAIL_CODE = 'email';
    const CAMPAIGN_PUSH_CODE = 'push';
    const CAMPAIGN_TRACKING_EXECUTING_STATUS = 'executing';
    const CAMPAIGN_TRACKING_ADDED_STATUS = 'added';
    const CAMPAIGN_TRACKING_COMPLETED_STATUS = 'completed';
    const CAMPAIGN_TRACKING_FAILED_STATUS = 'failed';

    const CAMPAIGN_INAPP_BANNER_NOTIFICATION_TITLE = "New notification";
    const CAMPAIGN_INAPP_BANNER_NOTIFICATION_BODY = 'You have a new notification.';

    const CAMPAIGN_TYPES = [
        [
            "code" => "email",
            "name" => "Email"
        ],
        [
            "code" => "push",
            "name" => "Push"
        ],
        [
            "code" => "inapp",
            "name" => "InApp"
        ]
    ];

    protected $fillable = [
        'app_group_id',
        'campaign_type',
        'subject',
        'from_name',
        'from_email',
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
        'updated_by',
    ];

    protected $with = ['variants', 'variants.translations', 'segments', 'actions', 'schedules'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new \App\Scopes\AppGroupScope());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(CampaignVariant::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actions()
    {
        return $this->hasMany(CampaignAction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        return $this->hasMany(CampaignSchedule::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function segments()
    {
        return $this->belongsToMany(Segment::class, 'campaign_segment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function apps()
    {
        return $this->belongsToMany(Apps::class, 'campaign_app', 'app_id', 'campaign_id');
    }

    /**
     * Get platform for a campaign.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    /*public function campaign_type()
    {
        return $this->hasOne(Lookup::class, 'code', 'campaign_type');
    }*/

    public function campaign_tracking()
    {
        return $this->hasMany(CampaignTracking::class, 'campaign_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(AppGroup::class, 'app_group_id');
    }

    public function linkTracking()
    {
        return $this->hasMany(LinkTrackings::class, 'rec_id');
    }
}
