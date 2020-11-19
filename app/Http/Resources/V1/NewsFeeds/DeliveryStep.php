<?php

namespace App\Http\Resources\V1\NewsFeeds;

use App\Campaign;
use App\CampaignAction;
use App\CampaignSchedule;
use App\Lookup;
use App\NewsFeed;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     *
     */
    public function process($data, NewsFeed $newsFeed)
    {
        if (array_search(NewsFeed::STEP_DELIVERY, NewsFeed::STEP_LEVEL) > array_search($newsFeed->step, NewsFeed::STEP_LEVEL))
            $newsFeed->step = NewsFeed::STEP_DELIVERY;


        $newsFeed->start_time = $data['dateTime']['start']['date'] . ' ' . $data['dateTime']['start']['hours'] . ':' . $data['dateTime']['start']['mints'] . ':00';
        if ($data['dateTime']['end']['date'] != null)
            $newsFeed->end_time = $data['dateTime']['end']['date'] . ' ' . $data['dateTime']['end']['hours'] . ':' . $data['dateTime']['end']['mints'] . ':00';

        $newsFeed->segment_id = $data['segment'] != -1 ? $data['segment'] : null;
        $newsFeed->location_id = $data['location'] != -1 ? $data['location'] : null;

        $newsFeed->save();

        return $newsFeed->refresh();
    }

    public function getStep($newsFeed)
    {
        $step2 = (object)[];
        $step2->step = 'delivery';
        $step2->segment = $newsFeed->segment_id;
        $step2->location = $newsFeed->location_id;

        $step2->dateTime = (object)[];

        $step2->dateTime->start = (object)[];

        if ($newsFeed->start_time == null) {
            $step2->dateTime->start->date = "";
            $step2->dateTime->start->hours = 0;
            $step2->dateTime->start->mints = 0;
        } else {
            $step2->dateTime->start->date = explode(" ", $newsFeed->start_time)[0];
            $step2->dateTime->start->hours = (int)explode(":", explode(" ", $newsFeed->start_time)[1])[0];
            $step2->dateTime->start->mints = (int)explode(":", explode(" ", $newsFeed->start_time)[1])[1];
        }


        $step2->dateTime->end = (object)[];

        if ($newsFeed->end_time == null) {
            $step2->dateTime->end->date = "";
            $step2->dateTime->end->hours = 0;
            $step2->dateTime->end->mints = 0;
        } else {
            $step2->dateTime->end->date = explode(" ", $newsFeed->end_time)[0];
            $step2->dateTime->end->hours = (int)explode(":", explode(" ", $newsFeed->end_time)[1])[0];
            $step2->dateTime->end->mints = (int)explode(":", explode(" ", $newsFeed->end_time)[1])[1];
        }

        return $step2;
    }
}