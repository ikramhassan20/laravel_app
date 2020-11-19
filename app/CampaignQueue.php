<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignQueue extends Model
{
    protected $table = 'campaign_queue';

    protected $fillable = [
        'campaign_id',
        'status',
        'priority',
        'error_message',
        'start_at',
        'details'
    ];

    protected $dates = ['start_at', 'created_at'];

    protected static $priority = [
        'high' => '1',
        'medium' => '2',
        'low' => '3',
    ];

    const STATUS_AVAILABLE = 'Available';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_FAILED = 'Failed';
    const STATUS_COMPLETE = 'Complete';

    /**
     * @param string $key
     *
     * @return string
     */
    public static function priority($key)
    {
        return isset(self::$priority[$key]) ? self::$priority[$key] : self::$priority['medium'];
    }

    /**
     * @return string
     */
    public function priorityKey()
    {
        return collect(self::$priority)->filter(function ($item) {
            return ($item === $this->priority) ? $item : null;
        })->keys()->first();
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
