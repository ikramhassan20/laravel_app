<?php

namespace App\Http\Controllers\Api\Stats;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SummaryController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('stats');
    }

    public function index(Request $request)
    {
        return $this->resourceClass->all($request);

    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return $this->resourceClass->summary($request);
    }

    public function getEmailCampaign(Request $request)
    {
        return $this->resourceClass->emailCampaign($request);
    }

    public function getConversationCampaign(Request $request)
    {
        return $this->resourceClass->conversationCampaign($request);
    }

    public function getCampaignUserLatLng(Request $request, $version, $type, $deviceType = null)
    {
        return $this->resourceClass->getUsers($type, $deviceType);
    }

    public function getCampaignStatsCount(Request $request)
    {
        return $this->resourceClass->getCampaignStatsCount($request);
    }

    public function getConversionCount(Request $request)
    {
        return $this->resourceClass->getConversionCount($request);
    }

    public function getNewsFeedCount(Request $request)
    {
        return $this->resourceClass->getNewsFeedCount($request);
    }

    public function getUserStatsCount(Request $request)
    {
        return $this->resourceClass->getUserStatsCount($request);
    }

    public function getRecentApps(Request $request)
    {
        return $this->resourceClass->getRecentApps($request);
    }
}
