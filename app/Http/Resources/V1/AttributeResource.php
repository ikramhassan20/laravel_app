<?php

namespace App\Http\Resources\V1;

use App\Apps;
use App\AppUsers;
use App\Attribute;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Components\RenderAttributePaginatedResponse;
use App\Helpers\CommonHelper;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\AttributeData;
use App\User;
use App\UserPackageHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\PHP;


class AttributeResource implements ResourcesContract, ProcessResourceDataContract
{

    use ParseResponse, ResourcesSteps, RenderAttributePaginatedResponse;

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = $this->attributePaginateResponse(Attribute::class, $request);
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response['data'],
                'data',
                $response['meta']
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to Attribute data'],
                'error'
            );
        }

    }

    public function create(\Illuminate\Http\Request $request)
    {
        try {
            /*$packageUsed = [
                "push" => 99999999,
                "inapp" => 999999999,
                "email" => 99999999999,
                "nfc" => 999999,
                "attribute_limit" => 100000000
            ];

            $limitExist = User::where("id", $request->user()->id)
                ->where("attribute_limit", ">", $packageUsed['attribute_limit'])
                ->first();


            if (!$limitExist) {
                throw new \Exception('Attribute limit reached, cannot make more Attributes');
            }*/

            $attribute = $this->process($request, new Attribute());
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $attribute,
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

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Invalid user.'],
                'error'
            );
        }

        $data = $this->parseResponse($request);
        if (!isset($model->id)) {

            // checking attribute creation limit
            $attributeLimit = !empty(config('engagement.api.limit.user_attribute')) ? config('engagement.api.limit.user_attribute') : 0;
            $checkAttributeCount = Attribute::where('app_group_id', $group->id)->count();
            if ($checkAttributeCount >= $attributeLimit) {
                throw new \Exception('Attributes limit exceeded');
            }

            $result = $model->where('app_group_id', '=', $group->id)->where('name', '=', $data['name'])->get();
            if (count($result) > 0) {
                throw new \Exception('Attribute already exist in current app group');
            } else {
                $data['app_group_id'] = $group->id;
                $data['level_type'] = 'custom';
                $data['alias'] = $data['name'];
                $data['source_table_name'] = '';
                $data['value_column'] = '';
                $data['text_column'] = '';
                $data['where_condition'] = '';
                $model->create($data);
            }
        } else {
            if ($model->app_group_id != $group->id) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Invalid user.'],
                    'error'
                );
            }
            $model->update($data);
        }
        return $model;
    }

    public function edit($request, $id)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $loginId = Auth::user()->id;
            $companyId = $request->user()->id;
            if ($companyId != $loginId) {
                throw new \Exception('Invalid user.');
            }
            $attribute = Attribute::where('id', $id)->first();
            if ($attribute->app_group_id != $appGroupID) {
                throw new \Exception('Invalid user.');
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $attribute,
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

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {

            $attribute = $this->process($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $attribute,
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

    public function getValuesAgainstCode($companyId, $appGroupId, $code)
    {
        try {
            $values = DB::table("attribute as a1")
                ->join("attribute_data as ad1", "a1.code", "=", "ad1.code")
                ->where("a1.app_group_id", $appGroupId)
                ->where("ad1.company_id", $companyId)
                ->where("a1.code", $code)
                ->where('ad1.row_id', 1)
                ->select("ad1.id", "ad1.value")
                ->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $values,
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

    public function takeActionAgainstAttribute($data)
    {
        try {

            if ($data['action'] == 'add') {

                if ($data['id'] == null) {
                    if (!empty($this->checkAttributeExist($data))) {
                        return $this->addResponse(
                            AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                            'error',
                            [AppStatusMessages::DUPLICATE_ENTRY],
                            'error'
                        );
                    } else {
                        $attrObj = new AttributeData();
                    }

                } else {
                    $checkAttribute = $this->checkAttributeExist($data);
                    if (!empty($checkAttribute) && $checkAttribute->id != $data['id']) {
                        return $this->addResponse(
                            AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                            'error',
                            [AppStatusMessages::DUPLICATE_ENTRY],
                            'error'
                        );
                    } else {
                        $attrObj = AttributeData::find($data['id']);
                    }
                }

                $attrObj->company_id = \Request::user()->id;
                $attrObj->row_id = 1;
                $attrObj->code = $data['code'];
                $attrObj->value = $data['value'];
                $attrObj->data_type = $data['type'];
                $attrObj->save();
            } else {
                $attrObj = AttributeData::find($data['id']);
                $attrObj->delete();
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                [
                    "message" => "Operation successful."
                ],
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

    public function checkAttributeExist($data)
    {
        $attrObj = AttributeData::where('company_id', \Request::user()->id)
            ->where('row_id', 1)
            ->where('code', $data['code'])
            ->where('value', $data['value'])
            ->where('data_type', $data['type'])
            ->first();

        return $attrObj;
    }

    public function updateAttributeFromCompany($request)
    {
        $data = $request->all();
        $company = $request->user();
        $headers = collect($request->headers)->filter(function ($value, $key) {
            return in_array($key, config('engagement.api.headers.app')) ? $value : null;
        });

        $columns = [];
        foreach ($request->except(['mode', '_token', 'app-name']) as $key => $part) {
            array_push($columns, $key);
        }
        $this->updateAttributeData($columns, $data, $company, $headers);
    }

    public function updateAttributeData($columns, $data, $company, $headers)
    {
        $appGroupId = CommonHelper::getAppGroupId($headers['app-id'][0], $headers['app-name'][0], $headers['device-type'][0], $company->id);

        $appUser = AppUsers::where([
            ['user_id', $data['user_id']],
            ['app_id', $headers['app-id'][0]],
            ['company_id', $company->id],
            ['app_group_id', $appGroupId],
            ['is_deleted', 0]
        ])->first();
//        $appId = Apps::where('app_id', $headers['app-id'][0])->first(['app.app_group_id']);
        $dbColumns = [
            "row_id",
            "company_id",
            "user_id",
            "app_id",
            "username",
            "firstname",
            "lastname",
            "email",
            "timezone",
            "latitude",
            "longitude",
            "country",
            "last_login",
            "enabled",
            "enable_notification",
            "email_notification",
            "status",
            "created_at",
            "updated_at",
            "deleted_at",
            "id",
            "app_name",
            "app_version",
            "app_build",
            "app_instance_id",
            "user_token",
            "device_token",
            "device_type",
            "lang",
            "is_logged_in",
            "is_revoked",
            "instance_id"
        ];
        $customVarcharAttributes = config('engagement.custom_varchar_attributes');
        $customVarcharAttributes = !empty($customVarcharAttributes) ? explode(',', $customVarcharAttributes) : [];
        $fields = array_diff($columns, $dbColumns);
        if (isset($appUser)) {
            foreach ($fields as $field) {

                $value = $data[$field];
                if (empty($value) || $value === null || $value === "\N") {
                    continue;
                }
                $attribute = Attribute::where("code", $field)->where('app_group_id', $appUser->app_group_id)->first();
                if ($attribute) {
                    if (in_array($field, $customVarcharAttributes)) {
                        $this->addMultipleDataAttribute($appUser, $company->id, $field, $value);
                    } else {
                        $attributeData = AttributeData::where('company_id', $company->id)->where('row_id', $appUser->row_id)->where('code', $field)->first();
                        if (empty($attributeData)) {
                            AttributeData::create([
                                'code' => $field,
                                'value' => $value,
                                'row_id' => $appUser->row_id,
                                'company_id' => $company->id,
                                'data_type' => 'user',
                                'created_by' => $company->id,
                                'updated_by' => $company->id
                            ]);
                        } else {
                            AttributeData::where('id', $attributeData->id)->update([
                                'code' => $field,
                                'value' => $value,
                                'row_id' => $appUser->row_id,
                                'company_id' => $company->id,
                                'data_type' => 'user',
                                'updated_by' => $company->id
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function addMultipleDataAttribute($appUser, $companyID, $key, $values)
    {
        AttributeData::where('row_id', $appUser->row_id)
            ->where('code', $key)
            ->where('data_type', 'user')
            ->where('company_id', $companyID)
            ->forceDelete();

        $valuesArray = [];
        if (strpos($values, ',') !== false) {
            $valuesArray = explode(',', $values);
        } else {
            array_push($valuesArray, $values);
        }

        $newAttributes = [];
        $isEmptyValueAdded = false;
        foreach ($valuesArray as $value) {

            if (empty($value)) {
                if ($isEmptyValueAdded == true) {
                    continue;
                }
                $isEmptyValueAdded = true;
            }

            $newAttributes[] = [
                'company_id' => $companyID,
                'row_id' => $appUser->row_id,
                'code' => $key,
                'value' => trim($value),
                'data_type' => 'user',
                'created_by' => $companyID,
                'updated_by' => $companyID,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
        }
        AttributeData::insert($newAttributes);
    }

    public function updateBulkAttributeFromCompany($companyList, $params)
    {
        $data = $params;
        $company = $companyList;

        $headers = collect($params)->filter(function ($value, $key) {
            return in_array($key, config('engagement.api.headers.bulkApp')) ? $value : null;
        });

        $header = [
            "app-id" => [$headers['app_id']],
            "app-name" => [$headers['app_name']],
            "device-type" => [$headers['device_type']]
        ];

        $headers['app-id'] = [
            $headers['app_id']
        ];
        unset($headers['app_id']);

        $columns = [];
        foreach ($data as $key => $part) {
            array_push($columns, $key);
        }

        $this->updateAttributeData($columns, $data, $company, $header);
    }

    public
    function removeAttribute($request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;

            if ($model->app_group_id != $appGroupID) {
                throw new \Exception('Invalid user.');
            }

            $model->delete();
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $model,
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