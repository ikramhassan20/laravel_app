<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SemanticBoardStatsController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('semanticStats');
    }

    public function boardTracking(Request $request, String $version, $id)
    {
        $request['boardId'] = $id;
        return $this->resourceClass->all($request);
    }

    public function boardTrackingExport(Request $request, String $version, $id)
    {
        $request['boardId'] = $id;
        return $this->resourceClass->all($request, false);
    }


    public function boardStats(String $version, $id)
    {
        return $this->resourceClass->boardStats($id);
    }

    public function trackingStats(Request $request, String $version, $id)
    {
        return $this->resourceClass->trackingStats($request, $id);
    }

    public function boardViewsClicksCount(Request $request, String $version, $id)
    {
        $request['boardId'] = $id;
        return $this->resourceClass->getViewsClicksCount($request);
    }

    public function boardViewsClicksChart(Request $request, String $version, $id)
    {
        $request['boardId'] = $id;
        return $this->resourceClass->getViewsClicksChart($request);
    }

    public function boardCountriesChart(Request $request, String $version, $id)
    {
        $request['board_id'] = $id;
        return $this->resourceClass->getCountriesChart($request);
    }

    public function boardActivityChart(Request $request, String $version, $id)
    {
        $request['board_id'] = $id;
        return $this->resourceClass->getActivityChart($request);
    }

    public function boardLinkActivityStats(Request $request, String $version, $id)
    {
        $request['board_id'] = $id;
        return $this->resourceClass->getLinkActivityStats($request);
    }

    public function boardActivityStatsExport(Request $request, String $version, $id)
    {
        $request['board_id'] = $id;
        return $this->resourceClass->exportActivityStats($request);
    }


    public function getBoardVariants(Request $request, $version, $boardId)
    {
        $appGroupId = $request->user()->currentAppGroup()->id;
        return $this->resourceClass->getBoardVariants($appGroupId, $boardId);
    }

    public function resendNotification(Request $request)
    {
        return $this->resourceClass->resendNotification($request);
    }

    public  function boardUserStatsSteps(String $version, $boardId)
    {
        return $this->resourceClass->boardUserStatsSteps($boardId);
    }

    public function boardUserStats(Request $request, String $version, $boardId)
    {
        $appGroupId = $request->user()->currentAppGroup()->id;
        return $this->resourceClass->boardUserStats($boardId, $appGroupId);
    }

}
