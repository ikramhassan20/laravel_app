<?php

namespace App\Http\Controllers\Api\AppGroup;

use App\AppGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SetCurrentAppGroupController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('app_groups');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $version
     * @param  \App\AppGroup             $appGroup
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, String $version, AppGroup $group)
    {
        //dd($request);
        return $this->resourceClass->setCurrentAppGroup($request, $group);
    }
}
