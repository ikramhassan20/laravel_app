<?php

namespace App\Http\Resources\V1\Users;

use App\AppUserTokens;
use App\Attribute;
use App\AttributeData;
use App\Cache\AppUserLoginSignupCache;
use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use App\Components\ValidateAppHeadersParams;
use App\AppUsers;
use App\Concerns\exportUsers;
use App\Events\AppUserLoginCacheEvent;
use App\Events\AppUserSignupCacheEvent;
use App\Events\AppUserLogoutCacheEvent;
use App\Helpers\CommonHelper;
use App\Http\Resources\V1\AttributeResource;
use App\Traits\CommonTrait;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class ClientApps
{
    use ParseResponse, ValidateAppHeadersParams, exportUsers, CommonTrait;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|array
     */
    public function process(Request $request)
    {
        try {

            $errors = $this->validateAppRequest($request);
            if (!empty($errors)) {
                return $errors;
            }
            $user = $request->user();

            $userData = new App\SyncUserData($user->id);

            $userData->itemKey($this->params);

            if ($this->mode === AppUsers::USER_LOGIN) {
                $exists = $userData->exists();
                if ($exists === false) {
                    throw new \Exception("User not found");
                }
            }

            $response = $this->pre_compile_header_body_request($request);
            $response['app_group_id'] = CommonHelper::getAppGroupId($response['app_id'], $response['app_name'], $response['device_type'], $user->id);

            if ($response['mode'] === AppUsers::USER_LOGOUT) {
                $attribute = AppUsers::where([
                    ['user_id', $response['user_id']],
                    //['app_id', $response['app_id']], //** to do */
                    ['company_id', $user->id],
                    ['app_group_id', $response['app_group_id']],
                    ['is_deleted', 0],
                    ['deleted_at', NULL]
                ])->first();
                if (!$attribute) {
                    throw new \Exception("User not found");
                }

                $row_id = $attribute->row_id;
                $attribute_token = AppUserTokens::select('id')->where('row_id', $row_id)
                    ->where('user_token', $response['user_token'])
                    ->first();
                if ($attribute_token) {
                    $attribute_token->is_logged_in = 0;
                    $attribute_token->save();

                    $data = ['App user logged out successfully.'];
                    $response['app_user_object'] = $attribute;
                } else {
                    throw new \Exception('App User token is in-valid.');
                }
            } elseif ($response['mode'] === AppUsers::USER_LOGIN) {
                $attribute = AppUsers::where([
                    ['user_id', $response['user_id']],
                    //['app_id', $response['app_id']], //** to do */
                    ['company_id', $user->id],
                    ['app_group_id', $response['app_group_id']],
                    ['is_deleted', 0],
                    ['deleted_at', NULL]
                ])->first();

                if (!$attribute) {
                    throw new \Exception("User not found");
                }
            }
            if ($response['mode'] === AppUsers::USER_LOGIN || $response['mode'] === AppUsers::USER_REGISTER) {
                $data = $userData->save($response);
                $attributeObj = new AttributeResource();
                $attributeObj->updateAttributeFromCompany($request);
            }

            DB::beginTransaction();

            if ($response['mode'] == AppUsers::USER_UPDATE) {

                if (isset($response['extra_params'])) {
                    $appUser = new AppUsers();
                    $appUser = $appUser->where([
                        ['user_id', $response['user_id']],
                        //['app_id', $response['app_id']], //** to do */
                        ['company_id', $user->id],
                        ['app_group_id', $response['app_group_id']],
                        ['is_deleted', 0],
                        ['deleted_at', NULL]
                    ])->first();
                    if (!$appUser) {
                        throw new \Exception("App User Not Found.");
                    }

                    $attributes = exportUsers::getHeaders($response['app_group_id']);
                    $totalFillables = array_unique(array_merge($appUser->getFillable(), $attributes));

//                    DB::enableQueryLog();

                    $newAttributesData = [];
                    if (isset($response['extra_params']['user_data']) && !empty($response['extra_params']['user_data'])) {
                        //if (sizeof(array_unique(array_merge($totalFillables, array_keys($response['extra_params']['user_data'])))) > sizeof($totalFillables)) {
                        //throw new \Exception("Please enter valid user attribute(s).");
                        //}

                        // it will find user data array and get wrong columns
                        // @param 1: user input data array
                        // @param 2: full valid array
                        $user_data_array = array_diff(array_keys($response['extra_params']['user_data']), $totalFillables);

                        if (sizeof($user_data_array) == sizeof(array_keys($response['extra_params']['user_data']))) {
                            throw new \Exception("Please enter valid user attribute(s).");
                        }

                        $customVarcharAttributes = config('engagement.custom_varchar_attributes');
                        $customVarcharAttributes = !empty($customVarcharAttributes) ? explode(',', $customVarcharAttributes) : [];
                        foreach ($response['extra_params']['user_data'] as $key => $value) {

                            if (in_array($key, $appUser->getFillable())) {
                                $appUser->{$key} = $value;
                            } else {
                                if (!in_array($key, $user_data_array)) {

                                    if (in_array($key, $customVarcharAttributes)) {
                                        $newAttributesData = array_merge($newAttributesData, $this->addMultipleDataAttribute($appUser, $user, $key, $value));

                                    } else {
                                        $attributeData = AttributeData::select('id')
                                            ->where('row_id', $appUser->row_id)
                                            ->where('code', $key)
                                            ->first();

                                        if ($attributeData) {
                                            $attributeData->value = $value;
                                            $attributeData->save();
                                        } else {
                                            $newAttributesData[] = [
                                                'company_id' => $user->id,
                                                'row_id' => $appUser->row_id,
                                                'code' => $key,
                                                'value' => $value,
                                                'data_type' => 'user',
                                                'created_by' => $user->id,
                                                'updated_by' => $user->id,
                                                'created_at' => Carbon::now(),
                                                'updated_at' => Carbon::now()
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $appUser->save();
                    }
                    if (isset($response['extra_params']['action_data']) && !empty($response['extra_params']['action_data'])) {

                        foreach ($response['extra_params']['action_data'] as $key) {
                            $attribute = Attribute::select('id')->where('app_group_id', $response['app_group_id'])
                                ->where('attribute_type', 'action')
                                ->where('code', $key['code'])
                                ->first();
                            if (!$attribute) {
                                throw new \Exception("Please provide valid action attribute `" . $key['code'] . "`.");
                            }
                            $attributeData = AttributeData::select('id')->where('row_id', $appUser->row_id)
                                ->where('code', $key['code'])
                                ->where('value', $key['value'])
                                ->first();
                            if ($attributeData) {
                                $attributeData->value = $key['value'];
                                $attributeData->save();
                            } else {

                                $newAttributesData[] = [
                                    'company_id' => $user->id,
                                    'row_id' => $appUser->row_id,
                                    'code' => $key['code'],
                                    'value' => $key['value'],
                                    'data_type' => 'action',
                                    'created_by' => $user->id,
                                    'updated_by' => $user->id,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ];
                            }
                        }

                    }
                    if (isset($response['extra_params']['conversion_data']) && !empty($response['extra_params']['conversion_data'])) {
                        foreach ($response['extra_params']['conversion_data'] as $key) {
                            $attribute = Attribute::select('id')->where('app_group_id', $response['app_group_id'])
                                ->where('attribute_type', 'conversion')
                                ->where('code', $key['code'])
                                ->first();
                            if (!$attribute) {
                                throw new \Exception("Please provide valid conversion attribute `" . $key['code'] . "`.");
                            }
                            $attributeData = AttributeData::select('id')->where('row_id', $appUser->row_id)
                                ->where('code', $key['code'])
                                ->where('value', $key['value'])
                                ->first();
                            if ($attributeData) {
                                $attributeData->value = $key['value'];
                                $attributeData->save();
                            } else {

                                $newAttributesData[] = [
                                    'company_id' => $user->id,
                                    'row_id' => $appUser->row_id,
                                    'code' => $key['code'],
                                    'value' => $key['value'],
                                    'data_type' => 'conversion',
                                    'created_by' => $user->id,
                                    'updated_by' => $user->id,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ];
                            }
                        }
                    }

                    AttributeData::insert($newAttributesData);

                    //$response['app_user'] = $appUser;
                }
                $data = $response;
            }

            if ($response['mode'] === AppUsers::USER_LOGIN || $response['mode'] === AppUsers::USER_UPDATE) {
                //event(new AppUserLoginCacheEvent($response));
                $login_cache = new AppUserLoginSignupCache();
                $login_cache->saveAppUserLoginCache($response);
            } else if ($response['mode'] === AppUsers::USER_IMPORT) {
                event(new AppUserSignupCacheEvent($response));
            } else if ($response['mode'] === AppUsers::USER_LOGOUT) {
                $login_cache = new AppUserLoginSignupCache();
                $login_cache->saveAppUserLogoutCache($response);
                //event(new AppUserLogoutCacheEvent($response));
            } else {
                event(new AppUserSignupCacheEvent($response));
            }

            DB::commit();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $data,
                'data'
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function addMultipleDataAttribute($appUser, $user, $key, $values)
    {
        AttributeData::where('row_id', $appUser->row_id)
            ->where('code', $key)
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
                'company_id' => $user->id,
                'row_id' => $appUser->row_id,
                'code' => $key,
                'value' => trim($value),
                'data_type' => 'user',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

        }

        return $newAttributes;
    }

    public function pre_compile_header_body_request($request)
    {
        $body = json_decode($request->getContent(), true);
        $headers = collect($request->headers)->filter(function ($value, $key) {
            return in_array($key, config('engagement.api.headers.app')) ? $value : null;
        });
        $headers = $headers->toArray();
        $body['app_name'] = $headers['app-name'][0];
        $body['app_id'] = $headers['app-id'][0];
        $body['device_type'] = $headers['device-type'][0];
        $body['app_build'] = $headers['app-build'][0];
        $body['app_version'] = $headers['app-version'][0];
        $body['lang'] = (isset($headers['lang'][0])) ? $headers['lang'][0] : "en";
        return $body;
    }

    public function BulkProcess(\Illuminate\Http\Request $request, $param)
    {

        $errors = $this->validateBulkAppRequest($request, $param);
        if (!empty($errors)) {
            return $errors;
        }

        $user = $request->user();
        $groupId = $user->currentAppGroup()->id;
        $param = $this->params;
        $appGroupId = CommonHelper::getAppGroupId($param['app_id'], $param['app_name'], $param['device_type'], $user->id);
        $param['app_group_id'] = $appGroupId;
        $param['app-name'] = $request->header('app-name');

        $userData = new App\SyncUserData($user->id);

        $userData->itemKey($param);
        $data = $userData->save($param);

        // shifted below
        if ($data['mode'] === AppUsers::USER_IMPORT) {

            $attributeObj = new AttributeResource();
            $attributeObj->updateBulkAttributeFromCompany($user, $param);

        } else {

            if (!empty($param['extra_params'])) {

                $appUser = new AppUsers();

                $appUser = $appUser->select('row_id')->where([
                    ['user_id', $param['user_id']],
                    //['app_id', $param['app_id']], //** to do */
                    ['company_id', $user->id],
                    ['app_group_id', $param['app_group_id']],
                    ['is_deleted', 0],
                    ['deleted_at', NULL]
                ])->first();

                if (!$appUser) {
                    throw new \Exception("App User Not Found.");
                }

                $attributes = exportUsers::getHeaders($param['app_group_id']);
                $totalFillables = array_unique(array_merge($appUser->getFillable(), $attributes));
                $newAttributesData = [];
                if (isset($param['extra_params']['user_data']) && !empty($param['extra_params']['user_data'])) {

                    if (sizeof(array_unique(array_merge($totalFillables, array_keys($param['extra_params']['user_data'])))) > sizeof($totalFillables)) {
                        throw new \Exception("Please enter valid user attribute(s).");
                    }
                    $customVarcharAttributes = config('engagement.custom_varchar_attributes');
                    $customVarcharAttributes = !empty($customVarcharAttributes) ? explode(',', $customVarcharAttributes) : [];


                    foreach ($param['extra_params']['user_data'] as $key => $value) {
                        if (in_array($key, $appUser->getFillable())) {
                            $appUser->{$key} = $value;
                        } else {
                            if (in_array($key, $customVarcharAttributes)) {
                                $newAttributesData = array_merge($newAttributesData, $this->addMultipleDataAttribute($appUser, $user, $key, $value));

                            } else {
                                $attributeData = AttributeData::select('id')->where('row_id', $appUser->row_id)
                                    ->where('code', $key)->first();
                                if ($attributeData) {
                                    $attributeData->value = $value;
                                    $attributeData->save();
                                } else {

                                    $newAttributesData[] = [
                                        'company_id' => $user->id,
                                        'row_id' => $appUser->row_id,
                                        'code' => $key,
                                        'value' => $value,
                                        'data_type' => 'user',
                                        'created_by' => $user->id,
                                        'updated_by' => $user->id,
                                        'created_at' => Carbon::now(),
                                        'updated_at' => Carbon::now()
                                    ];
                                }
                            }
                        }
                    }
                    $appUser->save();
                }
                if (isset($param['extra_params']['action_data']) && !empty($param['extra_params']['action_data'])) {
                    foreach ($param['extra_params']['action_data'] as $key => $value) {
                        $attribute = Attribute::select('id')->where('app_group_id', $param['app_group_id'])
                            ->where('attribute_type', 'action')
                            ->where('code', $key)
                            ->first();
                        if (!$attribute) {
                            throw new \Exception("Please provide valid action attribute `" . $key . "`.");
                        }
                        $attributeData = AttributeData::select('id')->where('row_id', $appUser->row_id)
                            ->where('code', $key)->first();
                        if ($attributeData) {
                            $attributeData->value = $value;
                            $attributeData->save();
                        } else {

                            $newAttributesData[] = [
                                'company_id' => $user->id,
                                'row_id' => $appUser->row_id,
                                'code' => $key,
                                'value' => $value,
                                'data_type' => 'action',
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];
                            /* $attributeData = new AttributeData();
                             $attributeData->company_id = $user->id;
                             $attributeData->row_id = $appUser->row_id;
                             $attributeData->code = $key;
                             $attributeData->value = $value;
                             $attributeData->data_type = 'action';
                             $attributeData->created_by = $user->id;
                             $attributeData->updated_by = $user->id;
                             $attributeData->save(); */
                        }
                    }

                }
                if (isset($param['extra_params']['conversion_data']) && !empty($param['extra_params']['conversion_data'])) {
                    foreach ($param['extra_params']['conversion_data'] as $key => $value) {
                        $attribute = Attribute::select('id')->where('app_group_id', $param['app_group_id'])
                            ->where('attribute_type', 'conversion')
                            ->where('code', $key)
                            ->first();
                        if (!$attribute) {
                            throw new \Exception("Please provide valid conversion attribute `" . $key . "`.");
                        }
                        $attributeData = AttributeData::select('id')->where('row_id', $appUser->row_id)
                            ->where('code', $key)->first();
                        if ($attributeData) {
                            $attributeData->value = $value;
                            $attributeData->save();
                        } else {

                            $newAttributesData[] = [
                                'company_id' => $user->id,
                                'row_id' => $appUser->row_id,
                                'code' => $key,
                                'value' => $value,
                                'data_type' => 'conversion',
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ];
                            /* $attributeData = new AttributeData();
                             $attributeData->company_id = $user->id;
                             $attributeData->row_id = $appUser->row_id;
                             $attributeData->code = $key;
                             $attributeData->value = $value;
                             $attributeData->data_type = 'conversion';
                             $attributeData->created_by = $user->id;
                             $attributeData->updated_by = $user->id;
                             $attributeData->save(); */
                        }
                    }
                }

                AttributeData::insert($newAttributesData);

                /*foreach($param['extra_params'] as $extra_params){
                    $extra_params['row_id'] = $data['row_id'];
                    $attribute = self::saveAttribute($groupId, $extra_params, 'user');
                    $attributeData = self::saveAttributeData($attribute, $extra_params['value'], $extra_params['row_id']);
                }*/
            }
        }

//      event(new AppUserSignupCacheEvent($param));
        $signup_cache = new AppUserLoginSignupCache();
        $signup_cache->saveAppUserSignupCache($data);
        return $data;
    }

    public function BulkActionProcess(\Illuminate\Http\Request $request, $param, $type)
    {
        $user = $request->user()->currentAppGroup();
        $groupId = $user->id;
        $attribute = self::saveAttribute($groupId, $param, $type);
        $attributeData = self::saveAttributeData($attribute, $param['value'], '');
        return $attributeData;
    }

    private function saveAttribute($groupId, $param, $type)
    {
        $attribute = Attribute::select('id')->where('app_group_id', $groupId)->where('code', $param['key'])->first();
        if (empty($attribute)) {
            $attribute = new Attribute();
        }

        $attribute->app_group_id = $groupId;
        $attribute->code = $param['key'];
        $attribute->name = $param['key'];
        $attribute->alias = $param['key'];
        $attribute->level_type = 'custom';
        $attribute->data_type = $param['data_type'];
        $attribute->attribute_type = $type;
        $attribute->length = $param['length'];
        $attribute->source_table_name = '';
        $attribute->value_column = '';
        $attribute->text_column = '';
        $attribute->where_condition = '';
        $attribute->save();
        return $attribute;
    }

    private function saveAttributeData($attribute, $value, $_row_id)
    {
        $companyId = Auth::user()->id;
        if (isset($_row_id) && $_row_id != "") {
            $attributeData = AttributeData::select('id')->where('company_id', $companyId)
                ->where('code', $attribute->code)
                ->where('row_id', $_row_id)
                ->first();

        } else {
            $attributeData = AttributeData::select('id')->where('company_id', $companyId)->where('code', $attribute->code)->first();
        }
        //$attributeData = AttributeData::where('company_id', $companyId)->where('code', $attribute->code)->first();
        if (empty($attributeData)) {
            $attributeData = new AttributeData();
        }

        $attributeData->company_id = $companyId;
        if (isset($_row_id) && $_row_id != "") {
            $attributeData->row_id = $_row_id;
        } else {
            $attributeData->row_id = self::generateRowId($companyId);
        }
        $attributeData->code = $attribute->code;
        $attributeData->value = $value;
        $attributeData->data_type = $attribute->attribute_type;
        $attributeData->created_by = $companyId;
        $attributeData->save();
        return $attributeData;
    }
}