<?php

namespace App\Http\Controllers\Api\Company\Lookup;

use App\Lookup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LookupFiltersController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('lookup');
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
        return $this->resourceClass->filters($request);
    }
}
