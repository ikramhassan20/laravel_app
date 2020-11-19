<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\CampaignAction;
use App\CampaignQueue;
use App\CampaignSchedule;
use App\Lookup;
use App\Traits\CommonTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryStep
{
    use CommonTrait;

    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     *
     */
    public function process($data, Campaign $campaign)
    {

        if (array_search(Campaign::STEP_DELIVERY, Campaign::STEP_LEVEL) > array_search($campaign->step, Campaign::STEP_LEVEL))
            $campaign->step = Campaign::STEP_DELIVERY;

        if ($data['subStep'] == Campaign::DELIVERY_TYPE_SCHEDULE) {
            if ($data['selectedScheduleType']['value'] == Campaign::SCHEDULE_WEEKLY) {
                CampaignSchedule::where("campaign_id", $campaign->id)->delete();
                foreach ($data['selectedDays'] as $day) {
                    $campaignScheduleDays = new CampaignSchedule();
                    $campaignScheduleDays->campaign_id = $campaign->id;
                    $campaignScheduleDays->day = $day['value'];
                    $campaignScheduleDays->save();
                }
            }

        }

        if ($data['subStep'] == Campaign::DELIVERY_TYPE_ACTION) {
            CampaignAction::where("campaign_id", $campaign->id)
                ->where("action_type", "trigger")
                ->delete();

            foreach ($data['deliveryActions'] as $action) {
                $campaignAction = new CampaignAction();
                $campaignAction->campaign_id = $campaign->id;
                $campaignAction->action_id = $action['id'];
                $campaignAction->value = $action['value'];
                $campaignAction->action_type = 'trigger';
                $campaignAction->save();
            }
            $campaign->action_trigger_delay_value = $data['actionDelay']['value'];
            $campaign->action_trigger_delay_unit = $data['actionDelay']['unit'];
        }

        $campaign->delivery_type = $data['subStep'];

        $campaign->schedule_type = $data['selectedScheduleType']['value'];
        $campaign->start_time = $data['dateTime']['start']['date'] . ' ' . $data['dateTime']['start']['hours'] . ':' . $data['dateTime']['start']['mints'] . ':10';
        if ($data['dateTime']['end']['date'] != null)
            $campaign->end_time = $data['dateTime']['end']['date'] . ' ' . $data['dateTime']['end']['hours'] . ':' . $data['dateTime']['end']['mints'] . ':30';

        if ($data['subStep'] == Campaign::DELIVERY_TYPE_SCHEDULE) {
            if ($data['selectedScheduleType']['value'] == Campaign::SCHEDULE_ONCE) {
                $campaign->end_time = null;
            }
        }


        if ($data['deliveryEnable']) {
            $campaign->delivery_control = 1;
            $campaign->delivery_control_delay_value = $data['deliveryControl']['value'];
            $campaign->delivery_control_delay_unit = $data['deliveryControl']['selectedUnit']['value'];
        } else {
            $campaign->delivery_control = 0;
        }

        $campaign->priority = $data['deliveryControl']['selectedPriority']['value'];
        $campaign->capping = $data['cappingValue'] ? 1 : 0;

        $campaign->Save();

        return $campaign->fresh();

    }

    public function getStep($campaignId)
    {
        $deliveryObj = (object)[];
        $campaign = DB::table("campaign")
            ->where("id", $campaignId)
            ->first();

        $deliveryObj->step = "delivery";
        $deliveryObj->subStep = $campaign->delivery_type;
        $deliveryObj->deliveryEnable = $campaign->delivery_control == 1 ? true : false;
        $deliveryObj->selectedScheduleType = (object)[];


        if ($campaign->schedule_type == null) {
            $deliveryObj->selectedScheduleType->label = "Once";
            $deliveryObj->selectedScheduleType->value = "once";
        } else {
            $deliveryObj->selectedScheduleType->label = ucfirst($campaign->schedule_type);
            $deliveryObj->selectedScheduleType->value = $campaign->schedule_type;
        }

        $deliveryObj->selectedDays = [];
        if ($campaign->schedule_type == "weekly") {

            $days = DB::table("campaign_schedule")
                ->where("campaign_id", $campaignId)
                ->select("day")->get();

            foreach ($days as $day) {
                $dayObj = (object)[];
                $dayObj->label = ucfirst($day->day);
                $dayObj->value = $day->day;
                $deliveryObj->selectedDays[] = $dayObj;
            }
        }

        $deliveryObj->dateTime = (object)[];

        $deliveryObj->dateTime->start = (object)[];

        if ($campaign->start_time == null) {
            $deliveryObj->dateTime->start->date = "";
            $deliveryObj->dateTime->start->hours = 0;
            $deliveryObj->dateTime->start->mints = 0;
        } else {
            $deliveryObj->dateTime->start->date = explode(" ", $campaign->start_time)[0];
            $deliveryObj->dateTime->start->hours = sprintf("%02d", explode(":", explode(" ", $campaign->start_time)[1])[0]);
            $deliveryObj->dateTime->start->mints = sprintf("%02d", explode(":", explode(" ", $campaign->start_time)[1])[1]);
        }


        $deliveryObj->dateTime->end = (object)[];

        if ($campaign->end_time == null) {
            $deliveryObj->dateTime->end->date = "";
            $deliveryObj->dateTime->end->hours = 0;
            $deliveryObj->dateTime->end->mints = 0;
        } else {
            $deliveryObj->dateTime->end->date = explode(" ", $campaign->end_time)[0];
            $deliveryObj->dateTime->end->hours = sprintf("%02d", explode(":", explode(" ", $campaign->end_time)[1])[0]);
            $deliveryObj->dateTime->end->mints = sprintf("%02d", explode(":", explode(" ", $campaign->end_time)[1])[1]);
        }

        $deliveryObj->deliveryControl = (object)[];
        $deliveryObj->deliveryControl->value = $campaign->delivery_control_delay_value;
        $deliveryObj->deliveryControl->selectedUnit = (object)[];


        if ($campaign->delivery_control_delay_unit == null) {
            $deliveryObj->deliveryControl->selectedUnit->label = "Minute";
            $deliveryObj->deliveryControl->selectedUnit->value = "minute";
        } else {
            $deliveryObj->deliveryControl->selectedUnit->label = ucfirst($campaign->delivery_control_delay_unit);
            $deliveryObj->deliveryControl->selectedUnit->value = $campaign->delivery_control_delay_unit;
        }

        $deliveryObj->deliveryControl->selectedPriority = (object)[];
        $deliveryObj->deliveryControl->selectedPriority->label = ucfirst($campaign->priority);
        $deliveryObj->deliveryControl->selectedPriority->value = $campaign->priority;


        $campaignAction = CampaignAction::where("campaign_id", $campaign->id)
            ->where("action_type", "trigger")
            ->get();

        $deliveryObj->deliveryActions = [];

        foreach ($campaignAction as $action) {
            $obj = (object)[];
            $obj->id = $action->action_id;
            $obj->value = $action->value;
            $deliveryObj->deliveryActions[] = $obj;
        }

        $deliveryObj->actionDelay = (object)[];
        $deliveryObj->actionDelay->value = $campaign->action_trigger_delay_value == null ? 1 : $campaign->action_trigger_delay_value;
        $deliveryObj->actionDelay->unit = $campaign->action_trigger_delay_unit == null ? "second" : $campaign->action_trigger_delay_unit;

        $deliveryObj->deliveryApiTrigger = (object)[];
        $deliveryObj->deliveryApiTrigger->code = $campaign->code;
        $deliveryObj->cappingValue = $campaign->capping == 1 ? true : false;

        return $deliveryObj;
    }
}