<?php

namespace App\Http\Controllers\Api;

use App\Board;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class SemanticBoardController extends Controller
{

    public function __construct()
    {
        $this->setResourceClass('semantic');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    public function show(String $version, $boardId)
    {
        return $this->resourceClass->get($boardId);
    }

    public function getFilters($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getSideFilters($appGroupId);
    }

    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }

    public function update(Request $request, String $version, Board $board)
    {
        return $this->resourceClass->update($request, $board);
    }

    public function getExportUsers($version, $boardId){
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->exportUsers($boardId, $appGroupId);
    }

    /**
     * push tracking service
     */
    public function trackingService(Request $request)
    {
        return $this->resourceClass->trackingService($request);
    }

    public function getBoardQueueListing(Request $request)
    {
        return $this->resourceClass->boardQueuesListing($request);
    }

    public function updateBoardQueueStatus(Request $request)
    {
        return $this->resourceClass->updateBoardQueueStatus($request);
    }
}
