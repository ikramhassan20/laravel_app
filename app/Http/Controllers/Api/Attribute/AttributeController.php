<?php

namespace App\Http\Controllers\Api\Attribute;

use App\Attribute;
use App\Http\Controllers\Controller;
use App\AttributeData;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('attribute');
    }

    public function index(Request $request)
    {
        return $this->resourceClass->all($request);

    }

    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }

    public function update(Request $request, String $version, Attribute $id)
    {
        return $this->resourceClass->update($request, $id);
    }

    public function destroy(Request $request, String $version, Attribute $id)
    {
        return $this->resourceClass->removeAttribute($request, $id);
    }

    public function show(Request $request,String $version, $id)
    {
        return $this->resourceClass->edit($request,$id);
    }

    public function getValues(String $version, $code)
    {
        return $this->resourceClass->getValuesAgainstCode(\Request::user()->id, \Request::user()->currentAppGroup()->id, $code);
    }

    public function takeAction(Request $request)
    {
        return $this->resourceClass->takeActionAgainstAttribute($request->all());
    }
}
