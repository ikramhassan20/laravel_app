<?php

namespace App\Helpers;

use App\CampaignCapRule;
use App\Cache\CampaignCappingCache;
use Carbon\Carbon;

class CampaignCappingHelper
{
    /**
     * @param $campaign_type
     * @return string
     */
    public static function cappingType($campaign_type)
    {
        $cap_type = CampaignCapRule::CAP_TYPE_PUSH;
        if ($campaign_type->isEmail()) { $cap_type = CampaignCapRule::CAP_TYPE_EMAIL; }
        if ($campaign_type->isInapp()) { $cap_type = CampaignCapRule::CAP_TYPE_INAPP; }

        return $cap_type;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param \Illuminate\Database\Eloquent\Model $cap_rule
     * @param int    $row_id
     * @param string $language_code
     * @param int $variant_id
     *
     * @return bool
     */
    public static function cappingEnabled($campaign, $cap_rule, $row_id, $language_code, $variant_id)
    {
        $rule = CampaignCappingCache::capping_rule_data($campaign, $row_id, $language_code, $variant_id);

        $interval = self::setCappingIntervalMethod($cap_rule->duration_unit);

        if (empty($rule)) {
            $start = Carbon::parse($campaign->start_time);
        } else {
            if (!isset($rule['start'])) {
                $rule['start'] = $campaign->start_time;
            }

            $start = Carbon::parse($rule['start']);

            if (!isset($rule['end'])) {
                $rule['end'] = Carbon::parse($rule['start'])->{$interval}($cap_rule->duration_value);
                $end = $rule['end'];
            } else {
                $end = Carbon::parse($rule['end']);
            }
        }

        $now = Carbon::now();
        $limit = $cap_rule->cap_limit;

        if (isset($end) && $now->gt($end)) {
            return false;
        }

        return ((!empty($rule['limit']) && $rule['limit'] >= $limit) && $now->gte($start) && $now->lt($end)) ? true : false;
    }

    /**
     * @param string $interval
     *
     * @return string
     */
    public static function setCappingIntervalMethod($interval)
    {
        return 'add'.ucfirst($interval);
    }
}