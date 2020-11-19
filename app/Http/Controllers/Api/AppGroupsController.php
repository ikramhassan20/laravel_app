<?php

namespace App\Http\Controllers\Api;

use App\AppGroup;
use App\Apps;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppGroupsController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('app_groups');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @param  \Illuminate\Http\Request  $request
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
     * @param  string         $version
     * @param  \App\AppGroup  $group
     *
     * @return \Illuminate\Http\Response
     */
    public function show(String $version, AppGroup $group)
    {
        return $this->resourceClass->show($group);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $version
     * @param  \App\AppGroup             $group
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, String $version, AppGroup $group)
    {
        return $this->resourceClass->update($request, $group);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string         $version
     * @param  \App\AppGroup  $group
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(String $version, AppGroup $group)
    {
        return $this->resourceClass->remove($group);
    }

    public function destroyApp(Request $request,String $version, Apps $appId)
    {
        return $this->resourceClass->removeAppResource($request, $appId);
    }
    public function applist(Request $request)
    {
        return $this->resourceClass->applist($request);
    }
    public function saveApp(Request $request)
    {
        return $this->resourceClass->saveApp($request);
    }
    public function editApp(Request $request,String $version,  $id)
    {
        return $this->resourceClass->editApp($request,$id);
    }
    public function  appUpdate(Request $request, String $version, Apps $app)
    {
        return $this->resourceClass->appUpdate($request, $app);
    }
    public function statusUpdate(Request $request)
    {
        return $this->resourceClass->statusUpdate($request);
    }
}

