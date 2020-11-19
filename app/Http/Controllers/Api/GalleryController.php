<?php

namespace App\Http\Controllers\Api;

use App\Gallery;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GalleryController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('gallery');
    }
    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }
    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }
    public function destroy(Request $request,String $version, Gallery $id)
    {
        return $this->resourceClass->removeGallery($request,$id);
    }
}
