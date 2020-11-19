<?php

namespace App;

use App\Concerns\CampaignConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignAction extends Model
{
    use CampaignConcerns; /*, SoftDeletes*/
    
    protected $table = 'campaign_action';

    protected $fillable = [
        'campaign_id',
        'action_id',
        'value',
        'action_type',
        'validity',
        'period'
    ];

    protected $hidden = ['deleted_at', 'updated_at', 'created_at'];

    const ACTION_TYPE_TRIGGER = 'trigger';
    const ACTION_TYPE_CONVERSION = 'conversion';

    /**
     * @return bool
     */
    public function isActionTypeTrigger()
    {
        return ($this->action_type === self::ACTION_TYPE_TRIGGER) ? true : false;
    }

    /**
     * @return bool
     */
    public function isActionTypeConversion()
    {
        return ($this->action_type === self::ACTION_TYPE_CONVERSION) ? true : false;
    }
}
