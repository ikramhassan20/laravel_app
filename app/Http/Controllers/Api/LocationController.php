<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    //

    public function __construct()
    {
        $this->setResourceClass('location');
    }

    public function index(Request $request)
    {
        //dd($request->all());
        return $this->resourceClass->all($request);

    }

    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }
    public function update(Request $request, String $version, Location $id)
    {
        return $this->resourceClass->update($request, $id);
    }

    public function destroy(Request $request,String $version, Location $Location)
    {
        return $this->resourceClass->removeLocation($request,$Location);
    }

    public function editlocation(String $version, $id)
    {
        return $this->resourceClass->editlocation($id);
    }

    public function areaStore(Request $request)
    {
        return $this->resourceClass->areaStore($request);
    }

    public function areaDelete(Request $request)
    {
        return $this->resourceClass->areaDelete($request);
    }
}
