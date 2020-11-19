<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NewsFeedStatsController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('newsFeedStats');
    }
    public function newsfeedViewsClicksCount(Request $request, String $version, $id)
    {
        $request['newsfeed_id'] = $id;
        return $this->resourceClass->getViewsClicksCount($request);
    }
    public function newsfeedViewsClicksChart(Request $request, String $version, $id)
    {
        $request['newsfeed_id'] = $id;
        return $this->resourceClass->getViewsClicksChart($request);
    }
    public function newsFeedStats(Request $request, String $version, $id)
    {
        $request['newsfeed_id'] = $id;
        return $this->resourceClass->newsFeedStats($id);
    }

}
