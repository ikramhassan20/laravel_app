<?php

namespace App\Http\Controllers\Api\Company;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PresetsController extends Controller
{
    /**
     * CampaignsController constructor.
     */
    public function __construct()
    {
        $this->setResourceClass('company_presets');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getSegmentPreSetsFilters($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getAttributes($appGroupId);
    }

    public function getSegmentsList($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getSegmentsList($appGroupId);
    }

    public function getCampaignPreSets(Request $request, $version)
    {
        $appGroupId = $request->user()->currentAppGroup()->id;
        return $this->resourceClass->getCampaignPreSetsFromResource($request, $appGroupId);
    }

    public function getLanguagesBySearching($version, $searching)
    {
        return $this->resourceClass->getLanguages($searching);
    }

    public function getSegmentsBySearching($version, $searching)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getSegments($appGroupId, $searching);
    }

    public function getAttributes($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getAttributeList($appGroupId);
    }

    public function getUsersBySearching($version, $searching, $campaignType, $deviceType) // need to further discuss about further filter-ation on the base of appGroupId
    {
        return $this->resourceClass->getUsers(\Request::user()->currentAppGroup()->id, $searching, $campaignType, $deviceType);
    }

    public function getNewsFeedPreSets(Request $request, $version)
    {
        $appGroupId = $request->user()->currentAppGroup()->id;
        return $this->resourceClass->getNewsFeedPreSetsFromResource($request, $appGroupId);
    }
}
