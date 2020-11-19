<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\CampaignAction;
use App\CampaignApp;
use App\Lookup;

class ConversionStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, Campaign $campaign)
    {
        if (array_search(Campaign::STEP_CONVERSION, Campaign::STEP_LEVEL) > array_search($campaign->step, Campaign::STEP_LEVEL))
            $campaign->step = Campaign::STEP_CONVERSION;


        CampaignAction::where("campaign_id", $campaign->id)
            ->where("action_type", "conversion")
            ->delete();

        foreach ($data['totalSelectedConversions'] as $conversion) {
            $campaignAction = new CampaignAction();
            $campaignAction->campaign_id = $campaign->id;
            $campaignAction->action_id = $conversion['id'];
            $campaignAction->value = $conversion['value'];
            $campaignAction->action_type = "conversion";
            $campaignAction->period = $conversion['period'];
            $campaignAction->validity = $conversion['validity'];
            $campaignAction->save();
        }


        CampaignApp::where("campaign_id", $campaign->id)->delete();

        foreach ($data['apps'] as $app) {
            $saveApp = new CampaignApp();
            $saveApp->campaign_id = $campaign->id;
            $saveApp->app_id = $app;
            $saveApp->save();
        }

        $campaign->save();

        return $campaign->fresh();
    }

    public function getStep($campaignId)
    {
        $conversionObj = (object)[];
        $conversionObj->step = "conversion";

        $campaignAction = CampaignAction::where("campaign_id", $campaignId)
            ->where("action_type", "conversion")
            ->get();

        $conversionObj->totalSelectedConversions = [];
        foreach ($campaignAction as $conversion) {
            $obj = (object)[];
            $obj->id = $conversion->action_id;
            $obj->value = $conversion->value;
            $obj->validity = $conversion->validity;
            $obj->period = $conversion->period;
            $conversionObj->totalSelectedConversions[] = $obj;
        }
        $conversionObj->apps = CampaignApp::where("campaign_id", $campaignId)->pluck("app_id");
        return $conversionObj;
    }
}