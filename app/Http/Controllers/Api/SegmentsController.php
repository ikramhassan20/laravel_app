<?php

namespace App\Http\Controllers\Api;

use App\Jobs\testjob;
use App\Segment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SegmentsController extends Controller
{
    /**
     * SegmentsController constructor.
     */
    public function __construct()
    {
        $this->setResourceClass('segments');
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
     * @param  \App\Segment $segment
     *
     * @return \Illuminate\Http\Response
     */
    public function show(String $version, $segmentId)
    {
        return $this->resourceClass->get($segmentId);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string $version
     * @param  \App\Segment $segment
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, String $version, Segment $segment)
    {
        return $this->resourceClass->update($request, $segment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $version
     * @param  \App\Segment $segment
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(String $version, Segment $segment)
    {
        return $this->resourceClass->remove($segment);
    }

    public function getFilters($version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->segmentTagsCount($appGroupId);
    }

    public function getExportUsers($version, $segmentId)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->exportUsers($segmentId, $appGroupId);
    }

    public function changeStatus($version, $segmentId, $status)
    {
        return $this->resourceClass->changeStatus($segmentId, $status);
    }
}
