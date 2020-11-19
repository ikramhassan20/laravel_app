<?php
/**
 * Created by PhpStorm.
 * User: omair
 * Date: 2019-01-28
 * Time: 14:17
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class CampaignVariantTunnel extends Model
{
    protected $table = 'campaign_variant_tunnel';
    protected $fillable = [
        'variant_id',
        'distribution_value',

    ];

}