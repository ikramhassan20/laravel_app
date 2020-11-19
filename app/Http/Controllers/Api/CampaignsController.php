<?php

namespace App\Http\Controllers\Api;

use App\Campaign;
use App\CampaignAction;
use App\CampaignCapRule;
use GuzzleHttp\Promise\RejectionException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CampaignsController extends Controller
{
    /**
     * CampaignsController constructor.
     */
    public function __construct()
    {
        $this->setResourceClass('campaigns');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $version
     * @param  \App\Campaign $campaign
     *
     * @return \Illuminate\Http\Response
     */
    public function show(String $version, $campaignId)
    {
        return $this->resourceClass->get($campaignId);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string $version
     * @param  \App\Campaign $campaign
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, String $version, Campaign $campaign)
    {
        return $this->resourceClass->update($request, $campaign);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $version
     * @param  \App\Campaign $campaign
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(String $version, Campaign $campaign)
    {
        return $this->resourceClass->remove($campaign);
    }

    public function getFilters($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getSideFilters($appGroupId);
    }

    public function getExportUsers($version, $campaignId)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->exportUsers($campaignId, $appGroupId);
    }

    /**
     * Action trigger api
     */
    public function actionTrigger(Request $request)
    {
        return $this->resourceClass->actionTrigger($request, new CampaignAction());
    }

    public function apiTrigger(Request $request)
    {
        return $this->resourceClass->apiTrigger($request);
    }

    public function conversionTrigger(Request $request)
    {
        return $this->resourceClass->conversionTrigger($request);
    }

    public function trackingService(Request $request)
    {
        return $this->resourceClass->trackingService($request);
    }

    public function getCappingSettings(Request $request)
    {
        return $this->resourceClass->getCappingSettings($request);
    }

    public function saveCappingSettings(Request $request)
    {
        return $this->resourceClass->saveCappingSettings($request->all());
    }

    public function getCampaignQueueListing(Request $request)
    {
        return $this->resourceClass->campaignQueuesListing($request);
    }

    public function updateCampaignQueueStatus(Request $request)
    {
        return $this->resourceClass->updateCampaignQueueStatus($request);
    }

    public function resendNotification(Request $request)
    {
        return $this->resourceClass->resendNotification($request);
    }
    public function  userActionList(Request $request)
    {
        return $this->resourceClass->userActionList($request);
    }
}
