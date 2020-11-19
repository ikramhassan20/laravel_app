<?php

namespace App\Http\Controllers\Api\Company;

use App\Lookup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LookupController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('lookup');
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->resourceClass->create($request, new Lookup());
    }

    /**
     * Display the specified resource.
     *
     * @param string $version
     * @param \App\Lookup $lookup
     *
     * @return \Illuminate\Http\Response
     */
    public function show(String $version, $id)
    {
        return $this->resourceClass->edit($id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $version
     * @param \App\Lookup $lookup
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(String $version, Request $request, Lookup $lookup)
    {
        return $this->resourceClass->delete($request, $lookup);
    }

    public function update(Request $request, String $version, Lookup $lookup)
    {
        return $this->resourceClass->update($request, $lookup);
    }
}
