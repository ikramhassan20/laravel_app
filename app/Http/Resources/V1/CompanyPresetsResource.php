<?php

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use App\Concerns\CampaignPresets;
use App\Concerns\FieldAttributes;
use App\Concerns\selectLangugage;
use App\Concerns\selectSegments;
use App\Concerns\selectUsers;
use App\Concerns\NewsFeedPresets;
use App\Lookup;
use App\Segment;
use Illuminate\Http\Request;
use App\CampaignTemplate;
use App\Campaign;
use Illuminate\Support\Facades\DB;


class CompanyPresetsResource
{
    use ParseResponse, FieldAttributes, CampaignPresets, selectLangugage, selectSegments, selectUsers, NewsFeedPresets;

    /**
     * Get list of campaigns.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttributes($appGroupId)
    {
        try {

            $filters = [];
            $fields = FieldAttributes::segmentAttributeFields($appGroupId);

            foreach ($fields as $field) {
                $mainObj = (object)[];
                $mainObj->id = $field->code;
                $mainObj->data = (object)[];
                $mainObj->data->type = $field->attribute_type;
                $mainObj->label = $field->code;
                $mainObj->optgroup = $field->attribute_type;

                if ($field->data_type == "VARCHAR") {
                    $mainObj->type = 'string';
                    if (strtolower($mainObj->label) == "email") {
                        $mainObj->validation = (object)[];
                        $mainObj->validation->callback = '';
                    }
//                    if (strtolower($mainObj->label) == "currency") {
//                        $mainObj->input = 'select';
//                        $mainObj->values = array_sort(config('engagement.currency_attribute.values'));
//                        $mainObj->operators = ['equal', 'not_equal', 'is_null', 'is_not_null'];
//                    }
//                    $mainObj->value_separator = ',';
                } elseif ($field->data_type == "INT") {
                    $mainObj->type = 'integer';
                    $mainObj->validation = (object)[];
                    $mainObj->validation->min = 0;
                } elseif ($field->data_type == "DATE") {
                    /*$mainObj->type = 'date';
                    $mainObj->validation = (object)[];
                    $mainObj->validation->format = 'YYYY/MM/DD';
                    $mainObj->plugin = 'datepicker';
                    $mainObj->plugin_config = (object)[];
                    $mainObj->plugin_config->format = 'yyyy-mm-dd';
                    $mainObj->plugin_config->todayBtn = 'linked';
                    $mainObj->plugin_config->todayHighlight = true;
                    $mainObj->plugin_config->autoclose = true;*/

                    $mainObj->type = 'datetime';
                    $mainObj->plugin = 'datetimepicker';
                    $mainObj->plugin_config = (object)[];
                    $mainObj->plugin_config->format = "YYYY-MM-DD";
                    //$mainObj->plugin_config->format = "YYYY/MM/DD HH:mm";
                    //$mainObj->plugin_config->debug = true;
                } elseif ($field->data_type == "SELECT") {
                    $mainObj->type = 'string';
                    $mainObj->input = 'select';
                    $mainObj->values = [];
                    $values = FieldAttributes::getValuesAgainstSelectAttribute($field, request()->user()->id);
                    foreach ($values as $value) {
                        $mainObj->values[$value] = $value;
                    }
                    $mainObj->operators = ['equal', 'not_equal', 'is_null', 'is_not_null'];
                }
                $filters[] = $mainObj;
            }

            $segmentObj = (object)[];
            $segmentObj->queryBuilderFilters = $filters;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segmentObj,
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

    public function getSegmentsList($appGroupId)
    {
        try {
            $segmentList = DB::table('segment')
                ->select(['id AS value', 'name AS label'])
                ->where('app_group_id', $appGroupId)
                ->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segmentList,
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

    public function getCampaignPreSetsFromResource($request, $appGroupId)
    {

        try {
            $obj = (object)[];
            $obj->campaignTypes = CampaignPresets::getCampaignTypes();
            $obj->templates = $this->getCampaignTemplates($obj->campaignTypes, $appGroupId);
            $obj->inAppPushData = CampaignPresets::getCampaignInAppPushData();
            $obj->campaignConversion = CampaignPresets::getCampaignActionData($request, $appGroupId, Lookup::LOOKUP_CODE_CONVERSION);
            $obj->campaignAction = CampaignPresets::getCampaignActionData($request, $appGroupId, Lookup::LOOKUP_CODE_ACTION);
            $obj->campaignApps = CampaignPresets::getCampaignApps($appGroupId);
            $obj->languages = CampaignPresets::getDefaultLanguage();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $obj,
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

    public function getCampaignTemplates($campaignTypes, $appGroupId)
    {
        try {
            $templates = [];
            foreach ($campaignTypes as $campaignType) {
                $templateObj = (object)[];
                $innerObj = (object)[];
                $templateObj->children = [];
                $templateObj->type = $campaignType["name"];

                if ($campaignType["name"] == "Email") {
                    $innerObj->childType = "CompanyTemplates";
                    $innerObj->content = [];
                    $innerObj->content = CampaignPresets::getCompanyTemplate("Email");
                    $templateObj->children[] = clone $innerObj;

                    $innerObj->childType = "CampaignTemplates";
                    $innerObj->content = [];
                    $innerObj->content = CampaignPresets::getCampaignTemplate($appGroupId);
                    $templateObj->children[] = clone $innerObj;

                } else if ($campaignType["name"] == "Push") {
                    $innerObj->childType = "Push";
                    $innerObj->content = [];
                    $innerObj->content = CampaignPresets::getCompanyTemplate("Push");
                    $templateObj->children[] = clone $innerObj;

                } else {

                    $inAppTemplates = CampaignPresets::getCompanyTemplate("InApp");

                    foreach ($inAppTemplates as $temp) {
                        $innerObj->childType = ucfirst(strtolower($temp->type));
                        unset($temp->type);
                        $innerObj->content = [];
                        $innerObj->content[] = $temp;
                        $templateObj->children[] = clone $innerObj;
                    }

                }

                $templates[] = $templateObj;
            }

            return $templates;

        } catch (\Exception $exception) {

            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }


    }

    public function getLanguages($searching)
    {
        try {
            $languages = selectLangugage::searchForTheLanguages($searching);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $languages,
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

    public function getSegments($appGroupId, $searching)
    {
        try {

            $segments = selectSegments::searchForTheSegments($appGroupId, $searching);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $segments,
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

    public function getAttributeList($appGroupId)
    {
        try {

            $fields = FieldAttributes::segmentAttributeFields($appGroupId);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $fields,
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

    public function getUsers($appGroupId, $searching, $campaignType, $deviceType)
    {
        try {

            $users = selectUsers::selectUsersBySearch($appGroupId, $searching, $campaignType, $deviceType); // ask later if on the base of appGroupId

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $users,
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

    public function getNewsFeedPreSetsFromResource($request, $appGroupId)
    {
        try {
            $obj = (object)[];
            $obj->step1 = (object)[];
            $obj->step1->newsFeedTemplates = NewsFeedPresets::getNewsFeedTemplates();
            $obj->step1->languages = CampaignPresets::getDefaultLanguage();
            $obj->step2 = (object)[];
            $obj->step2->segments = NewsFeedPresets::getSegments($appGroupId);
            $obj->step2->locations = NewsFeedPresets::getLocations($appGroupId);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $obj,
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
}