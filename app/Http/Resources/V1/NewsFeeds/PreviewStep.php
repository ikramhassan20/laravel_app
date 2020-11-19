<?php

namespace App\Http\Resources\V1\NewsFeeds;

use App\NewsFeed;

class PreviewStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, NewsFeed $newsFeed)
    {
        $newsFeed->step = NewsFeed::STEP_PREVIEW;
        $data['status'] == 'delete' ? $newsFeed->deleted_at = date("Y-m-d h:i:s") : $newsFeed->status = $data['status'];
        $newsFeed->save();

        return $newsFeed->fresh();
    }
}