<?php

namespace App\Concerns;

use App\Location;
use App\Segment;
use App\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


trait NewsFeedPresets
{
    public static function getNewsFeedTemplates()
    {
        return Template::where('type', 'newsfeed')
            ->select('id', 'name', 'content_url as template')
            ->get();
    }

    public static function getSegments($appGroupId)
    {
        return Segment::where('app_group_id', $appGroupId)
            ->where('is_active', 1)
            ->select('id', 'name')
            ->get();
    }

    public static function getLocations($appGroupId)
    {
        return Location::where('app_group_id', $appGroupId)
            ->select('id', 'name')
            ->get();
    }
}