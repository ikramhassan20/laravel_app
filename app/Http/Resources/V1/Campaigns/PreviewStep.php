<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\CampaignQueue;
use App\Traits\CommonTrait;
use Carbon\Carbon;
use Log;
use App\Helpers\CommonHelper as Helper;

class PreviewStep
{
    use CommonTrait;

    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, Campaign $campaign)
    {
        $campaign->step = Campaign::STEP_PREVIEW;
        $campaign->status = $data['status'];
        $campaign->save();

        Helper::saveCampaignSegmentsUnion($campaign->id, $campaign->app_group_id);
        $this->processCampaignQueues($campaign->fresh());

        return $campaign->fresh();
    }

    public function processCampaignQueues($campaign)
    {
        if ($campaign->delivery_type !== "schedule") {
            return false;
        }

        $dates = array();
        $start = $campaign->start_time;
        $end = $campaign->end_time;

        if ($campaign->schedule_type == "once") {
            $this->createCampaignQueues($campaign);
        }

        if ($campaign->schedule_type == "daily") {
            $dates = self::getAllDateTimeBetweenTwoDates($start, $end);
        }

        if ($campaign->schedule_type == "weekly") {
            $schedules = $campaign->schedules()->select('day')->get();
            foreach ($schedules as $schedule) {
                $dates = array_merge($dates, self::getAllDateTimeBetweenTwoDates($start, $end, $schedule->day));
            }
        }

        $_queue = CampaignQueue::where('campaign_id', '=', $campaign->id)->first();

        if (empty($_queue) AND count($dates) > 0) {
            foreach ($dates as $date) {
                $this->createCampaignQueues($campaign, $date);
            }
        }
    }

    private function createCampaignQueues($campaign, $startDateTime = null)
    {
        $startDateTime = isset($startDateTime) ? $startDateTime : $campaign->start_time;
        $sendingDate = Carbon::parse($startDateTime);

        $details = [
            "campaignId" => $campaign->id,
            "scheduleType" => $campaign->schedule_type,
            "sendingDate" => $sendingDate->format('Y-m-d'),
            "campaignDay" => ""
        ];
        $CampaignCheck = Campaign::where('id', '=', $campaign->id)->where('status', '!=', 'suspended')->first();
        if ($CampaignCheck) {
            $queue = new CampaignQueue();
            $queue->campaign_id = $campaign->id;
            $queue->status = CampaignQueue::STATUS_AVAILABLE;
            $queue->priority = CampaignQueue::priority(isset($result->campaign_priority) ? $campaign->campaign_priority : 'medium');
            $queue->start_at = $startDateTime;
            $queue->details = json_encode($details);
            $queue->save();
        }
    }
}