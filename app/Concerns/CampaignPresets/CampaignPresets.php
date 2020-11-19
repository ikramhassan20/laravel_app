<?php

namespace App\Concerns;

use App\Campaign;
use App\Template;
use App\Language;
use App\Lookup;
use App\Apps;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


trait CampaignPresets
{
    public static function getCampaignTypes()
    {
        return Campaign::CAMPAIGN_TYPES;
    }

    public static function getCompanyTemplate($type)
    {
        if (strtolower($type) != strtolower(Template::TYPE_INAPP)) {
            return Template::where('type', $type)
                ->select('id', 'content_url as content', 'thumbNail')
                ->get();
        }

        return Template::whereNotIn('type', [Template::TYPE_EMAIL, Template::TYPE_PUSH, Template::NEWS_FEED])
            ->select('id', 'content_url as content', 'type')
            ->get();
    }

    public static function getCampaignTemplate($appGroupId)
    {
        $emailContent = DB::table('campaign as c1')
            ->join('campaign_variant as cv1', 'c1.id', '=', 'cv1.campaign_id')
            ->join('translation as t1', 'cv1.id', '=', 't1.translatable_id')
            ->join('language as l1', 't1.language_id', '=', 'l1.id')
            ->where('c1.app_group_id', $appGroupId)
            ->where('c1.campaign_type', 'email')
            ->where('t1.translatable_type', 'campaign')
            ->where('l1.code', 'en')
            ->select('c1.id', 'c1.name', 't1.template as content')
            ->take(5)
            ->get();

        $campaignId = -1;
        $itr = 0;
        foreach ($emailContent as $content) {
            if ($campaignId != $content->id) {
                $content->content = \Cache::get($content->content);
                $isJson = is_string($content->content) && is_array(json_decode($content->content, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
                if ($isJson) {
                    $content->content = \GuzzleHttp\json_decode($content->content, true)['templateInfo']['template'];
                }
                $campaignId = $content->id;
            } else {
                unset($emailContent[$itr]);
            }
            $itr++;
        }

        return $emailContent;
    }

    public static function getCampaignInAppPushData()
    {
        $dataObj = (object)[];
        $platformId = DB::table('lookup')->where('code', Lookup::LOOKUP_CODE_PLATFORM)
            ->first()->id;

        $platformList = DB::table('lookup')->where('parent_id', $platformId)
            ->select('id', 'name')
            ->get();

        $messageTypeId = DB::table('lookup')->where('code', Lookup::LOOKUP_CODE_MESSAGE_TYPE)
            ->first()->id;

        $messageTypeList = DB::table('lookup')->where('parent_id', $messageTypeId)
            ->select('id', 'name')
            ->get();

        $layoutTypeId = DB::table('lookup')->where('code', Lookup::LOOKUP_CODE_LAYOUT)
            ->first()->id;

        $layoutTypeList = DB::table('lookup')->where('parent_id', $layoutTypeId)
            ->select('id', 'name')
            ->get();

        $devicePositionId = DB::table('lookup')->where('code', Lookup::LOOKUP_CODE_DEVICE_POSITION)
            ->first()->id;

        $devicePositionList = DB::table('lookup')->where('parent_id', $devicePositionId)
            ->select('id', 'name')
            ->get();

        $actionId = DB::table('lookup')->where('code', Lookup::LOOKUP_CODE_ACTION)
            ->first()->id;

        $actionList = DB::table('lookup')->where('parent_id', $actionId)
            ->select('id', 'name')
            ->get();


        $dataObj->platformList = $platformList;
        $dataObj->messageTypeList = $messageTypeList;
        $dataObj->layoutTypeList = $layoutTypeList;
        $dataObj->devicePositionList = $devicePositionList;
        $dataObj->actionList = $actionList;

        return $dataObj;
    }

    public static function getCampaignActionData($request, $appGroupId, $type)
    {
        $companyId = $request->user()->id;

        $actionLookUpData = DB::table("attribute")
            ->where("attribute_type", $type)
            ->where("app_group_id", $appGroupId)
            ->select('id', 'code', 'name')
            ->get();

        $arr = [];
        foreach ($actionLookUpData as $data) {
            $obj = (object)[];
            $obj->id = $data->id;
            $obj->name = $data->name;
            $obj->code = $data->code;
            $value = DB::table('attribute_data')
                ->where('company_id', $companyId)
                ->where('attribute_data.code', $data->code)
                ->pluck('value')->toArray();
            $obj->values = $value;
            //if (!empty($obj->values))
            $arr[] = clone $obj;
        }

        return $arr;
    }

    public static function getCampaignApps($appGroupId)
    {
        $apps = Apps::where('app_group_id', $appGroupId)
            ->select("id", "name", "logo", "platform")
            ->get();
        return $apps;
    }

    public static function getDefaultLanguage()
    {
        $langArr = DB::table("language")
            ->whereNull("deleted_at")
            ->select("id", "name as label", "code as value", "image as imgUrl", "dir")
            ->get();

        $defaultLang = (object)[];
        foreach ($langArr as $obj) {
            if ($obj->label == Language::DEFAULT_LANGUAGE)
                $defaultLang = clone $obj;
        }

        return [
            $langArr,
            $defaultLang
        ];
    }
}