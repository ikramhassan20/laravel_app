<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\NewsFeed;
use Illuminate\Http\Request;


class NewsFeedController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('newsFeed');
    }

    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    public function store(Request $request)
    {
        return $this->resourceClass->create($request);
    }

    public function update(Request $request, String $version, NewsFeed $newsfeed)
    {
        return $this->resourceClass->update($request, $newsfeed);
    }

    public function show(String $version, $newsfeedId)
    {
        return $this->resourceClass->get($newsfeedId);
    }

    public function getFilters(String $version)
    {
        $appGroupId = \Request::user()->currentAppGroup()->id;
        return $this->resourceClass->getSideFilters($appGroupId);
    }

    public function getNewsFeedList(Request $request)
    {
        return $this->resourceClass->getNewsFeedList($request);
    }

    public function actionList(Request $request)
    {
        return $this->resourceClass->actionList($request);
    }

    public function newsFeedCount(Request $request)
    {
        return $this->resourceClass->newsFeedCount($request);
    }
}
