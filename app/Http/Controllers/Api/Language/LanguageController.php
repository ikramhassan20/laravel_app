<?php

namespace App\Http\Controllers\Api\Language;

use App\Http\Controllers\Controller;
use App\Language;
use App\Translation;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('language');
    }

    public function index(Request $request)
    {
       return $this->resourceClass->all($request);

    }
    public function store(Request $request)
    {
        //dd($request->all());
        return $this->resourceClass->create($request);
    }
    public function update(Request $request, String $version, Language $lang)
    {
        return $this->resourceClass->update($request, $lang);
    }
    public function destroy(Request $request,String $version, Language $lang)
    {
        return $this->resourceClass->removeLanguage($request,$lang);
    }
    public function show(String $version, $lang)
    {
        return $this->resourceClass->edit($lang);
    }

}
