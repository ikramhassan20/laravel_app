<?php

namespace App\Components;

use App\CampaignRateLimitRules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RateLimitingComponents
{
    /**
     * setting the interval time for specfic campaign messages
     * adding the time interval according the rate limit mean 2 and duration value  5 minutes
     * in campaign_start_date and created new field interval
     *
     * @param @int $campaignId
     * @param @array $campaignObj
     * @param int $rate_limit
     * @param int $duration_value
     * @param string $duration_unit
     *
     * @return array
     */
    public static function rateLimitingRules($campaignObj, $rate_limit, $duration_value, $duration_unit)
    {
        try {

            $chunksCount = ceil(count($campaignObj) / $duration_value);
            $skip = 0;
            $itr = 0;
            /**
             * implementing the loop on chunks count
             */
            for ($val = 0; $val < $chunksCount; $val++) {
                for ($i = 0; $i < $duration_value && ($skip + $i) < sizeof($campaignObj); $i++) {
                    $datetime = new Carbon($campaignObj[$skip + $i]['payload']['data']['start_date']);
                    $seconds = self::getSeconds($itr * $rate_limit, $duration_unit);
                    $campaignObj[$skip + $i]['payload']['data']['interval'] = $datetime->addSeconds($seconds)->format('Y-m-d H:i:s');
                }

                $skip = $skip + $duration_value;
                $itr++;
            }

            return $campaignObj;

        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            Log::error($error);
        }
    }

    public static function getSeconds($value, $unit)
    {
        $timeUnit = [
            "minutes" => 60,
            "days" => 86400,
            "weeks" => 604800,
        ];
        $seconds = $value * $timeUnit[$unit];
        return $seconds;
    }
}