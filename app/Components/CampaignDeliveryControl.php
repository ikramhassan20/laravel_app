<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\Campaign;
use App\CampaignQueue;
use App\CampaignTracking;
use App\Cache\CampaignTrackingCache;
use App\Helpers\CampaignDeliveryControlHelper;
use App\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Class CampaignDeliveryControl
 * @package App\Components
 * @todo campaign delivery control
 */
class CampaignDeliveryControl
{
    /**
     * @param int $campaign_id
     * @param int $row_id
     * @param string $language
     * @param int $variant_id
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function applyDeliveryControl($campaign_id, $row_id, $language, $variant_id)
    {
        try {
            // getting campaign information
            $campaign = Campaign::find($campaign_id);
            if(!isset($campaign)){
                Log::error('Campaign not found.');
                return false;
            }

            // getting campaign tracking from cache
            $tracking =(new CampaignTrackingCache)->getCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id);
            if(!isset($tracking)) {
                Log::error('Campaign tracking cache not found.');
                return false;
            }

            $last_sent_date = (isset($tracking->last_sent_date)) ? $tracking->last_sent_date : '';

            if($last_sent_date!="")
            {
                $intervalDate = '';
                switch (strtolower($campaign->delivery_control_delay_unit)) {
                    case 'minute':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addMinutes($campaign->delivery_control_delay_value);

                        break;
                    case 'day':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addDays($campaign->delivery_control_delay_value);

                        break;
                    case 'week':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addWeeks($campaign->delivery_control_delay_value);

                        break;
                    case 'month':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addMonths($campaign->delivery_control_delay_value);

                        break;
                }

                if (!empty($intervalDate)) {
                    $now = Carbon::now(config('app.timezone'));
                    if ($now->lt($intervalDate)) {
                        return false;
                    }
                }
            }
            return true;
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
            return false;
        }
    }
}
