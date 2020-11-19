<?php
/**
 * Created by PhpStorm.
 * User: omair
 * Date: 2019-01-25
 * Time: 15:17
 */

namespace App;

use App\Concerns\CampaignConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignRateLimitRules extends Model
{
    use CampaignConcerns;/*, SoftDeletes*/
    protected $table = 'campaign_rate_limit';
    protected $fillable = [
        'campaign_id',
        'rate_limit',
        'duration_unit',
        'duration_value',
    ];

}