<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 1/23/19
 * Time: 4:26 PM
 */

namespace App\Http\Resources\V1;

use App\AppUsers;
use App\Attribute;
use App\AttributeData;
use App\Components\Action\ActionListingValidator;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\NewsFeed\NewsFeedValidatorComponent;
use App\Components\ParseResponse;
use App\Components\RenderNewFeedPaginateResponse;
use App\Concerns\tagsCount;
use App\Helpers\CampaignValidation;
use App\Helpers\CommonHelper;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\NewsFeed;
use App\NewsFeedImpression;
use App\Template;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class NewsFeedResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, tagsCount;

    public function all(\Illuminate\Http\Request $request)
    {

        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = (new RenderNewFeedPaginateResponse())->renderpaginate($request);
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response['data'],
                'data',
                $response['meta']
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                $exception->getMessage(),
                'error'
            );
        }
    }

    public function create(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $data = $this->parseResponse($request);
            $newsfeed = $this->process($data, new NewsFeed());
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $newsfeed,
                'data'
            );
        } catch (\Exception $exception) {
            if ($exception->getCode() == AppStatusCodes::DUPLICATE_SQL_ENTRY_COLUMN) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    [AppStatusMessages::DUPLICATE_ENTRY],
                    'error'
                );
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    [AppStatusMessages::CANNOT_CREATE_RECORD],
                    $exception->getMessage()
                );
            }

        }
    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        switch (strtolower($request['step'])) {
            case 'compose':
                $class = (new NewsFeeds\ComposeStep);
                break;
            case 'delivery':
                $class = (new NewsFeeds\DeliveryStep);
                break;
            case 'confirm':
                $class = (new NewsFeeds\PreviewStep);
                break;
        }

        return $class->process($request, $model);
    }

    public function update(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $user = $request->user();
            $companyId = $request->user()->id;
            $group = $user->currentAppGroup();
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            if ($model->app_group_id != $group->id) {
                throw new \Exception('Invalid user.');
            }
            $data = $this->parseResponse($request);

            $newsfeed = $this->process($data, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $newsfeed,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function show(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function remove(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function get($newsfeedId)
    {
        try {

            $appGroupId = \Request::user()->currentAppGroup()->id;
            $newsFeedExist = NewsFeed::where("id", $newsfeedId)
                ->where("app_group_id", $appGroupId)
                ->first();

            if (!$newsFeedExist) {

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'error',
                    [
                        "status" => false,
                        "message" => "NewsFeed not found"
                    ],
                    'data'
                );
            }

            $stepArr = [];
            switch (strtolower($newsFeedExist->step)) {
                case 'compose':
                    $stepArr[] = (new NewsFeeds\ComposeStep);
                    break;
                case 'delivery':
                    $stepArr[] = (new NewsFeeds\ComposeStep);
                    $stepArr[] = (new NewsFeeds\DeliveryStep);
                    break;
                default:
                    $stepArr[] = (new NewsFeeds\ComposeStep);
                    $stepArr[] = (new NewsFeeds\DeliveryStep);
            }

            $getSteps = [];
            foreach ($stepArr as $step) {
                $getSteps[] = $step->getStep($newsFeedExist);
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $getSteps,
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                $exception->getMessage(),
                [AppStatusMessages::DUPLICATE_ENTRY]
            );
        }
    }

    public function getSideFilters($appGroupId)
    {
        try {

            $newsFeedType = (object)[];
            $newsFeedType->column = "Types";
            $newsFeedType->children = [];

            $types = Template::where("type", "newsfeed")
                ->pluck("name")->toArray();

            foreach ($types as $type) {
                $obj = (object)[];
                $obj->parent = 't1.name';
                $obj->value = $type;
                $newsFeedType->children[] = $obj;
            }

            $status = [

                "column" => "Status",
                "children" => [
                    [
                        "parent" => "nf1.status",
                        "value" => "active"
                    ],
                    [
                        "parent" => "nf1.status",
                        "value" => "draft"
                    ],
                    [
                        "parent" => "nf1.status",
                        "value" => "suspend"
                    ],
                ]
            ];

            $newsFeedSideFilters = [];
            $nFtags = (object)[];
            $nFtags->column = "Tags";
            $nFtags->children = [];
            $tags = tagsCount::findTagsCount($appGroupId, 'news_feed');

            foreach ($tags as $tag) {
                $tagObj = (object)[];
                $tagObj->parent = "nf1.tags";
                $tagObj->value = $tag->tags;
                $nFtags->children[] = $tagObj;
            }

            $newsFeedSideFilters[] = $newsFeedType;
            $newsFeedSideFilters[] = $status;
            $newsFeedSideFilters[] = $nFtags;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $newsFeedSideFilters,
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                $exception->getMessage(),
                'error'
            );
        }
    }

    /*
     * News feed apis
     */
    public function getNewsFeedList(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $user = $request->user();
            $group = $user->currentAppGroup();
            $companyid = $user->id;
            $unReadCount = 0;
            $response = array();
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $data = $this->parseResponse($request);
            $lat = $data['latitude'];
            $longitude = $data['longitude'];
            $radius = config('enums.migration.radius');
            $userId = $data['user_id'];

            $param = array(
                'appId' => $appId,
                'appName' => $appName,
                'device_type' => $devicetype,
                'user_id' => $data['user_id'],
                'company_id' => $companyid
            );
            $validator = new NewsFeedValidatorComponent($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            $appGroupValidation = (new CampaignValidation())->getAppGroupId($param);
            if ($appGroupValidation) {
                $appGroupId = $appGroupValidation->app_group_id;
                $param['company_id'] = $appGroupValidation->company_id;
                $getUsersRowIds = (new CommonHelper())->getRowIds($param);
                if ($getUsersRowIds) {
                    $userRowId = $getUsersRowIds->row_id;
                    $param['row_id'] = $userRowId;
                    $UserDeviceInfo = (new CommonHelper())->getUserDeviceInfo($param);
                    if ($UserDeviceInfo) {
                        $param['language_id'] = $UserDeviceInfo->language_id;
                        $param['language_code'] = $UserDeviceInfo->language_code;
                        $query = "(SELECT location.is_active,news_feed.*,
                                  (6371 * acos(cos(radians($lat)) * cos(radians(latitude))
                                  * cos(radians(longitude) - radians($longitude)) + sin(radians($lat)) 
                                  * sin(radians(latitude ))) ) AS distance 
                                  FROM news_feed 
                                  INNER JOIN location on location.id=news_feed.location_id
                                  AND (location.deleted_at is null AND location.is_active='1')
                                  INNER JOIN location_areas on
                                  news_feed.location_id=location_areas.location_id  
                                  AND location_areas.deleted_at is null
                                  AND news_feed.status='active'
                                  AND news_feed.app_group_id='$group->id'
                                  HAVING distance>0 AND distance < '$radius' ORDER BY news_feed.created_at desc)";
                        $newsFeed = DB::select(DB::raw($query));
                        if (count($newsFeed) > 0) {
                            $newsFeedCacheList = (new CommonHelper())->getNewFeedUsersFromSegmentCache($newsFeed, $userRowId, $appGroupId);
                            $newsFeedList = array_values($newsFeedCacheList);
                            $translationContent = (new CommonHelper())->getNewsFeedTranslation($newsFeedList, $param);
                            $newsFeedImpressionObj = new NewsFeedImpression();
                            $response = "";

                            if (!empty($translationContent)) {
                                foreach ($translationContent as $newsFeedArray) {
//                                    if (in_array($param['language_id'], $newsFeedArray)) {
                                    foreach ($newsFeedArray['news_feed_links'] as $link) {
                                        if (strtolower($link['category']) == strtolower($param['device_type'])) {
                                            $encodedUrl = url('') . '/trackLink?enc=' . base64_encode("newsfeed" . '/' . $newsFeedArray['news_feed_id'] . '/' . $userRowId . '/' . $link['value']);
                                            $newsFeedArray['content']['template'] = str_replace('nf_action_link', $encodedUrl, $newsFeedArray['content']['template']);
                                        }
                                    }
                                    $param['created_date'] = carbon::now();
                                    $param['location_id'] = $newsFeedArray['location_id'];
                                    $param['news_feed_id'] = $newsFeedArray['news_feed_id'];
                                    $newsFeedImpressionCheck = $newsFeedImpressionObj->where('news_feed_id', $newsFeedArray['news_feed_id'])->first();
                                    if (!$newsFeedImpressionCheck) {
                                        $unReadCount = $unReadCount + 1;
                                        $unReadStatus = 'true';
                                        $param['viewed'] = 0;
                                    } else {
                                        $param['viewed'] = 1;
                                        $unReadCount = 0;
                                        $unReadStatus = 'false';
                                    }
                                    $this->saveNewsFeedImpression($param);
                                    //$data['htmlArr'] = '<div class="compose_layout_holder">' . $newsFeedArray['content'] . '</div>';
                                    $data['htmlArr'] = $newsFeedArray['content'];
                                    $data['htmlArr']['template'] = '<div class="compose_layout_holder">' . $data['htmlArr']['template'] . '</div>';

                                    //dd($data);
                                    $html = view('newsfeed.newsfeed_view', $data)->render();
                                    $response .= html_entity_decode($html);
//                                    }
                                }
                                $finalResponse = array(
                                    "total_newsfeed" => count($translationContent),
                                    "unread_newsfeed" => $unReadCount,
                                    'content' => $response
                                );
                            } else {
                                $data['htmlArr'] = "";
                                //dd($data);
                                $html = view('newsfeed.newsfeed_view', $data)->render();
                                $response = html_entity_decode($html);
                                $finalResponse = array(
                                    "total_newsfeed" => 0,
                                    "unread_newsfeed" => 0,
                                    'content' => $response
                                );
                            }
                        } else {
                            $data['htmlArr'] = "";
                            //dd($data);
                            $html = view('newsfeed.newsfeed_view', $data)->render();
                            $response = html_entity_decode($html);
                            $finalResponse = array(
                                "total_newsfeed" => 0,
                                "unread_newsfeed" => 0,
                                'content' => $response
                            );
                        }
                    }

                }
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $finalResponse,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function saveNewsFeedImpression($param)
    {
        $param['platform'] = $param['device_type'];
        //$param['viewed'] = 0;
        unset($param['appId'], $param['appName'], $param['company_id'], $param['device_type']);
        $response = NewsFeedImpression::create($param);
        return $response;
    }

    public function newsFeedCount(Request $request)
    {
        try {
            $userRowId = '';
            $user = $request->user();
            $group = $user->currentAppGroup();
            $companyid = $user->id;
            $unReadCount = 0;
            $response = array();
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $data = $this->parseResponse($request);
            $lat = $data['latitude'];
            $longitude = $data['longitude'];
            $radius = config('enums.migration.radius');
            $userId = $data['user_id'];
            $param = array(
                'appId' => $appId,
                'appName' => $appName,
                'device_type' => $devicetype,
                'user_id' => $data['user_id'],
                'company_id' => $companyid
            );

            $validator = new NewsFeedValidatorComponent($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            $appGroupValidation = (new CampaignValidation())->getAppGroupId($param);
            if ($appGroupValidation) {
                $appGroupId = $appGroupValidation->app_group_id;
                $param['company_id'] = $appGroupValidation->company_id;
                $getUsersRowIds = (new CommonHelper())->getRowIds($param);
                if ($getUsersRowIds) {
                    $userRowId = $getUsersRowIds->row_id;
                    $param['row_id'] = $userRowId;
                    $UserDeviceInfo = (new CommonHelper())->getUserDeviceInfo($param);
                    if ($UserDeviceInfo) {
                        $param['language_id'] = $UserDeviceInfo->language_id;
                        $query = "(SELECT location.is_active,news_feed.*,
                                  (6371 * acos(cos(radians($lat)) * cos(radians(latitude))
                                  * cos(radians(longitude) - radians($longitude)) + sin(radians($lat)) 
                                  * sin(radians(latitude ))) ) AS distance 
                                  FROM news_feed 
                                  INNER JOIN location on location.id=news_feed.location_id
                                  AND (location.deleted_at is null AND location.is_active='1')
                                  INNER JOIN location_areas on
                                  news_feed.location_id=location_areas.location_id  
                                  AND location_areas.deleted_at is null
                                  AND news_feed.status='active'
                                  AND news_feed.app_group_id='$group->id'
                                  HAVING distance>0 AND distance < '$radius' ORDER BY distance)";
                        $newsFeed = DB::select(DB::raw($query));
                        if (count($newsFeed) > 0) {
                            $newsFeedCacheList = (new CommonHelper())->getNewFeedUsersFromSegmentCache($newsFeed, $userRowId, $appGroupId);
                            $newsFeedList = array_values($newsFeedCacheList);
                            $translationContent = (new CommonHelper())->getNewsFeedTranslation($newsFeedList, $param);
                            $newsFeedImpressionObj = new NewsFeedImpression();
                            $response = "";
                            foreach ($translationContent as $newsFeedArray) {
                                if (in_array($param['language_id'], $newsFeedArray)) {
                                    $param['location_id'] = $newsFeedArray['location_id'];
                                    $param['news_feed_id'] = $newsFeedArray['news_feed_id'];
                                    $newsFeedImpressionCheck = $newsFeedImpressionObj->where('news_feed_id', $newsFeedArray['news_feed_id'])->first();
                                    if (!$newsFeedImpressionCheck) {
                                        $unReadCount = $unReadCount + 1;
                                        $unReadStatus = 'true';
                                    } else {
                                        $unReadCount = 0;
                                        $unReadStatus = 'false';

                                    }
                                }
                            }
                            $finalResponse = array(
                                "total_newsfeed" => count($translationContent),
                                "unread_newsfeed" => $unReadCount,
                            );

                        } else {
                            throw new \Exception('No NewsFeed found against this lat long');
                        }
                    }

                }
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $finalResponse,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function actionList(\Illuminate\Http\Request $request)
    {
        try {
            $user = $request->user();
            $companyid = $user->id;
            $userArray = array();
            $data = $this->parseResponse($request);
            $dataType = $data['data_type'];
            $appId = $request->header('app-id');
            $appName = $request->header('app-name');
            $devicetype = $request->header('device-type');
            $version = $request->header('app-version');
            $build = $request->header('app-build');
            $validator = new ActionListingValidator($data);
            if (!empty($validator->errors)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    $validator->errors,
                    'error'
                );
            }
            $userParam = array(
                'appId' => $appId,
                'device_type' => $devicetype,
                'version' => $version,
                'build' => $build,
                'appName' => $appName,
                'company_id' => $companyid
            );
            $appGroupId = (new CampaignValidation())->getAppGroupId($userParam);
            if ($appGroupId) {
                $appGroupId = $appGroupId->app_group_id;
                switch ($dataType) {
                    case 'all':
                        $response = $this->getActions('all', $appGroupId, $companyid, $data['user_id']);
                        break;
                    case 'action':
                        $response = $this->getActions('action', $appGroupId, $companyid, $data['user_id']);
                        break;
                    case 'conversion':
                        $response = $this->getActions('conversion', $appGroupId, $companyid, $data['user_id']);
                        break;

                }
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    AppStatusMessages::SUCCESS,
                    $response,
                    'data'
                );
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                $exception->getMessage(),
                'error'
            );
        }

    }

    public function getActions($dataType, $appGroupId, $companyid, $userID)
    {
        $action = array();
        $conversion = array();
        $user = array();
        $finalResponse = array();

        $rowIDs = [1];
        $appUser = AppUsers::select('row_id')
            ->where('app_group_id', $appGroupId)
            ->where('company_id', $companyid)
            ->where('user_id', $userID)
            ->first();

        if (!empty($appUser->row_id)) {
            array_push($rowIDs, $appUser->row_id);
        }

        if ($dataType == 'all') {
            $response = Attribute::leftjoin('attribute_data', 'attribute_data.code', '=', 'attribute.code')
                ->where('attribute.app_group_id', $appGroupId)
                ->where('attribute_data.company_id', $companyid)
                ->get(['attribute.code as code', 'attribute.attribute_type', 'attribute_data.value as value']);
        } else {
            $response = Attribute::where('app_group_id', $appGroupId)
                ->where('attribute_type', $dataType)
                ->get(['attribute.code as code', 'attribute.attribute_type']);
        }
        if (count($response) > 0) {
            for ($val = 0; $val < count($response); $val++) {
                $value = '';
                $attributeResponses = AttributeData::where('code', '=', $response[$val]['code'])
                    ->where('company_id', '=', $companyid)
                    ->whereIn('row_id', $rowIDs)
                    ->get();

                if (count($attributeResponses) > 0) {
                    foreach ($attributeResponses as $attributeResponse) {
                        if ($attributeResponse) {
                            $value = $attributeResponse->value;
                        }
                        if ($response[$val]['attribute_type'] == "action") {
                            $action[] = array(
                                'code' => $response[$val]['code'],
                                'attribute_type' => $response[$val]['attribute_type'],
                                'value' => $value
                            );
                        } else if ($response[$val]['attribute_type'] == "conversion") {
                            $conversion[] = array(
                                'code' => $response[$val]['code'],
                                'attribute_type' => $response[$val]['attribute_type'],
                                'value' => $value
                            );
                        }
                    }
                } else {
                    //if ($attributeResponses) {
                    //    $value = $attributeResponses->value;
                    //}
//                    if ($response[$val]['attribute_type'] == "action") {
//                        $action[] = array(
//                            'code' => $response[$val]['code'],
//                            'attribute_type' => $response[$val]['attribute_type'],
//                            'value' => $value
//                        );
//                    } else if ($response[$val]['attribute_type'] == "conversion") {
//                        $conversion[] = array(
//                            'code' => $response[$val]['code'],
//                            'attribute_type' => $response[$val]['attribute_type'],
//                            'value' => $value
//                        );
//                    }
                }
            }
        }
        $finalResponse = array(
            'action' => $action,
            'conversion' => $conversion
        );
        return $finalResponse;
    }

    /*
    * News feed apis
    */
}