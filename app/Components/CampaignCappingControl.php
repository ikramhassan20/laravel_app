<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\Cache\CampaignCappingCache;
use App\Campaign;
use App\CampaignQueue;
use App\CampaignTracking;
use App\Cache\CampaignTrackingCache;
use App\Helpers\CampaignCappingHelper;
use App\CampaignCapRule;
use App\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class CampaignCappingControl
 * @package App\Components
 * @todo campaign capping control
 */
class CampaignCappingControl
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param int $row_id
     * @param string $language_code
     * @param int $variant_id
     * @param datetime $_tracking_sent_at
     *
     */
    public static function setCappingInfo($campaign, $row_id, $language_code, $variant_id, $_tracking_sent_at){

        $app_group_id = $campaign->app_group_id;
        $campaign_capping = (isset($campaign->capping)) ? $campaign->capping : "0";

        if((int)$campaign_capping > 0){

            $cap_type = CampaignCappingHelper::cappingType($campaign->campaign_type);
            $cap_rule = CampaignCapRule::where('app_group_id', '=',$app_group_id)
                                            ->where('campaign_type', '=', $campaign->campaign_type)
                                            ->where('deleted_at', null)
                                            ->first();

            if (!empty($cap_type) && !empty($cap_rule->app_group_id)) {
                $capping = CampaignCappingHelper::cappingEnabled($campaign, $cap_rule, $row_id, $language_code, $variant_id);
                $data = CampaignCappingCache::capping_rule_data($campaign, $row_id, $language_code, $variant_id);
                $cappingData = CampaignCappingCache::getCappingCacheData($campaign, $row_id, $language_code, $variant_id);

                if ($capping === false) {
                    $interval = CampaignCappingHelper::setCappingIntervalMethod($cap_rule->duration_unit);
                    $duration = $cap_rule->duration_value;

                    $limit = !empty($data['limit']) ? $data['limit'] : 0;
                    if ($limit >= $cap_rule->cap_limit) {
                        unset($data);
                    }

                    $start = !empty($data['start']) ? $data['start'] : $campaign->start_time;
                    if ($_tracking_sent_at != "") {
                        $start = $_tracking_sent_at;
                    }

                    $endDate = Carbon::parse($start)->{$interval}($duration);
                    $end = !empty($data['end']) ? $data['end'] : $endDate->toDateTimeString();

                    $limit += 1;

                    $cappingData[] = [
                        'limit' => $limit,
                        'start' => $start,
                        'end'   => $end
                    ];

                    CampaignTrackingData::setCappingCacheData($campaign, $row_id, $cappingData, $language_code, $variant_id);
                }
            }
        }
    }

}
