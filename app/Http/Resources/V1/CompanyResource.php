<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 1/21/19
 * Time: 4:43 PM
 */

namespace App\Http\Resources\V1;

use App\AppGroup;
use App\Apps;
use App\AppUserActivity;
use App\AppUsers;
use App\AppUserTokens;
use App\Attribute;
use App\AttributeData;
use App\Cache\AppGroupSegmentCache;
use App\Cache\AppUserLoginSignupCache;
use App\Cache\CacheKeys;
use App\Cache\CampaignSegmentCache;
use App\CampaignTracking;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Components\RenderCompanyPaginateResponse;
use App\Concerns\exportUsers;
use App\Concerns\FieldAttributes;
use App\Events\AppUserSignupCacheEvent;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\ImportData;
use App\Jobs\RebuildCacheJob;
use App\LinkTrackings;
use App\LocationArea;
use App\NewsFeedImpression;
use App\Translation;
use App\User;
use App\UserPackageHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Package;
use App\BounceEmailLog;

class CompanyResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderCompanyPaginateResponse, FieldAttributes, exportUsers;

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = $this->RenderCompanyPaginateResponse(User::class, $request);
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
                ['Unable to Company data'],
                $exception->getMessage()
            );
        }

    }

    public function create(\Illuminate\Http\Request $request)
    {
    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
    }

    public function edit($id)
    {
        $userId = Auth::user()->id;
        try {

            if (!Auth::user()->isadmin) {
                if ($userId != $id) {
                    throw new \Exception('User Invalid');
                }
                if ($userId != $id) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                        'error',
                        ['Invalid user.'],
                        'error'
                    );
                }
            }
            $response = User::where('id', '=', $id)->first();
            $response->check_is_admin = Auth::user()->isadmin;
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            $response = [
                $exception->getMessage() => 'Unable to fetch company',
                'check_is_admin' => Auth::user()->isadmin
            ];
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                $response,
                'data'
            );
        }
    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $companyID = $request->user()->id;
            if (!Auth::user()->isadmin) {
                if ($companyID != $request['id']) {
                    throw new \Exception('Invalid user.');
                }
            }

            $model = $model->where('id', '=', $request['id'])->update([
                'name' => $request['name'],
                'logo' => $request['logo'],
                'is_active' => $request['is_active']
            ]);
            if ($model) {
                $res = array(
                    'status' => 200,
                    'message' => 'Updated',
                    'user' => User::where('id', '=', $request['id'])->first()
                );

            } else {
                $res = array(
                    'status' => 400,
                    'message' => 'failed'
                );
            }
            return $res;

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function statusUpdate(\Illuminate\Http\Request $request)
    {

        try {
            $response = $this->RenderCompanyStatusResponse($request);
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                '',
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to Company data'],
                $exception->getMessage()
            );
        }

    }

    public function userList(\Illuminate\Http\Request $request)
    {
        try {
            $response = $this->RenderCompanyUsersPaginateResponse(AppUsers::class, $request);
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
                ['Unable to Company Users data'],
                $exception->getMessage()
            );
        }
    }

    public function bouncedUsers(\Illuminate\Http\Request $request)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $queryChain = BounceEmailLog::join('app_user', 'bounce_email_log.row_id', '=', 'app_user.row_id')
                ->where('app_user.app_group_id', '=', $appGroupID);
            $totalCount = clone $queryChain;
            $totalCount = $totalCount->count();
            if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
                $status =  ($request['sideFilters'] == 'Active' ? 1 : 0);
                $queryChain->where('app_user.status', '=', $status);
            }

            if ($request['query'] != null) {
                $search = $request['query'];
                $columns = $request['columns'];
                $queryChain->where(function ($query) use ($search, $columns) {
                    $query->where('bounce_email_log.id', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.bounce_email', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.action', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.status', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.code', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.bounce_date', 'LIKE', "%{$search}%");
                    $query->orWhere('bounce_email_log.source_ip', 'LIKE', "%{$search}%");
                });
            }
            $totalFiltered = clone $queryChain;
            $totalFiltered = $totalFiltered->count();
            isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
            $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
                ->limit($request['limit'])
                ->get(['bounce_email_log.*']);
            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];
            $response = [
                'meta' => $meta,
                'data' => $data
            ];
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response['data'],
                'data',
                $response['meta']
            );
        }
        catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }

    }


    public function bouncedUserDelete($request, $id)
    {
        try {
            \App\BounceEmailLog::where('id', '=', $id)->delete();
            return response()->json('Bounced log deleted successfully.');

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load delete'],
                $exception->getMessage()
            );
        }
    }

    public function unsubscribeUserList(\Illuminate\Http\Request $request)
    {
        try {
            $response = $this->RenderCompanyUsersPaginateResponse(AppUsers::class, $request, true);
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
                ['Unable to Company Users data'],
                $exception->getMessage()
            );
        }
    }

    public function userStats(\Illuminate\Http\Request $request, $id)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $companyId = $request->user()->id;
            $authId = Auth::user()->id;
            if ($companyId != $authId) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_METHOD_NOT_ALLOWED,
                    'error',
                    'User Not Valid',
                    'error'
                );
            }
            $userValid = AppUsers::where('row_id', '=', $id)->first();

            if ($userValid->app_group_id != $appGroupID) {
                throw new \Exception('Invalid user.');
            }
            if ($userValid) {
                $appGroupId = $userValid->app_group_id;
                $group = Auth::user()->currentAppGroup();
                if ($group->id != $appGroupId) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_METHOD_NOT_ALLOWED,
                        'error',
                        'User Not Valid',
                        'error'
                    );
                }
                $response = AppUsers::where('row_id', $id)->select('email', 'user_id as userId', 'username as userName', 'country', 'app_id as appId', 'last_login as lastLogin', 'latitude', 'longitude', 'image_url', 'enable_notification', 'email_notification')->first();
                $userList = AppUserTokens::withTrashed()->where('row_id', $id)
                    ->orderBy('updated_at', 'DESC')
                    ->get([
                        'row_id',
                        'user_id', 'app_name', 'app_id', 'app_version', 'app_build',
                        'device_token', 'device_type', 'lang', 'is_logged_in', 'is_revoked', 'status', 'created_at', 'deleted_at'
                    ]);
                $userTab['campaign'] = AppUserActivity::join('campaign', 'campaign.id', '=', 'app_user_activity.campaign_id')
                    ->where('app_user_activity.row_id', '=', $id)
                    ->where('app_user_activity.rec_type', '=', 'conversion')
                    ->orderBy('app_user_activity.created_at', 'desc')
                    ->limit(10)->get([
                        'campaign.id as campaign_id',
                        'campaign.name as campaign_name',
                        'campaign.campaign_type as campaign_type',
                        'app_user_activity.event_id',
                        'app_user_activity.event_value',
                        'app_user_activity.created_at as date'
                    ]);

                $userTab['userAction'] = AppUserActivity::join('campaign', 'campaign.id', '=', 'app_user_activity.campaign_id')
                    ->where('app_user_activity.row_id', '=', $id)
                    ->where('app_user_activity.rec_type', '=', 'action_trigger')
                    ->orderBy('app_user_activity.created_at', 'desc')
                    ->get(['campaign.id as campaign_id',
                        'campaign.name as campaign_name',
                        'campaign.campaign_type as campaign_type',
                        'app_user_activity.event_id',
                        'app_user_activity.event_value',
                        'app_user_activity.created_at as date'
                    ]);

                $userTab2['campaign'] = DB::table('campaign_tracking as ct1')
                    ->join('campaign as c1', 'ct1.campaign_id', '=', 'c1.id')
                    ->where('c1.app_group_id', \Request::user()->currentAppGroup()->id)
                    ->where('ct1.row_id', $id)
                    ->where('ct1.sent', 1)
                    ->select('c1.campaign_type', 'c1.name', 'ct1.created_at')
                    ->orderBy('ct1.created_at', 'desc')
                    ->get();

                $userTab2['newsFeed'] = DB::table('link_tracking as l1')
                    ->where('l1.rec_type', 'newsfeed')
                    ->where('l1.row_id', $id)
                    ->select('l1.actual_url', 'l1.device_type', 'l1.created_at')
                    ->orderBy('l1.created_at', 'desc')
                    ->get();

                $userTab3['activity'] = DB::table('app_user_activity as aua1')
                    ->join('attribute as a1', 'aua1.event_id', '=', 'a1.id')
                    ->where('aua1.row_id', $id)
                    ->where('a1.attribute_type', '<>', 'user')
                    ->select('a1.attribute_type', 'a1.code', 'aua1.event_value', 'aua1.created_at')
                    ->orderBy('aua1.created_at', 'desc')
                    ->get();

                $userTab3['user'] = exportUsers::getUsers([$id], false);
                ksort($userTab3['user']);

                $userTab3['user_action'] = AttributeData::select('code', 'value')
                    ->where('row_id', $id)
                    ->where('data_type', 'action')
                    ->where('company_id', $companyId)
                    ->get();

                $finalResponse = array(
                    'userObj' => $response,
                    'usertab1' => $userTab,
                    'usertab2' => $userTab2,
                    'usertab3' => $userTab3,
                    'usertab4' => [
                        'attributeList' => $userList
                    ]

                );
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    AppStatusMessages::SUCCESS,
                    $finalResponse,
                    'data'
                );
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_METHOD_NOT_ALLOWED,
                    'error',
                    'User Not Valid',
                    'error'
                );
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                $exception->getMessage(),
                $exception->getMessage()
            );
        }

    }

    public function updatePassword(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $companyID = $request->user()->id;

            if (!Auth::user()->isadmin) {
                if ($companyID != $request['id']) {
                    throw new \Exception('Invalid user.');
                }
            }
            $rules = [
                'email' => 'required|email',
                'password' => [
                    'required',
                    'string',
                    'min:8',             // must be at least 8 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[*@!#%&$()?^~{}_+=[><.,-]+/', // must contain a special character
                ],
                'confirmPassword' => 'required|same:password'
            ];

            $validation = \Validator::make($request->all(), $rules);

            if ($validation->fails()) {
                $validation->errors()->add('password', 'Password must be at least 8 characters in length.');
                $validation->errors()->add('password', 'Password must contain at least one lowercase letter.');
                $validation->errors()->add('password', 'Password must contain at least one uppercase letter.');
                $validation->errors()->add('password', 'Password must contain at least one digit.');
                $validation->errors()->add('password', 'Password must contain a special character.');

                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    [$validation->errors()->all()],
                    'error'
                );
            }

            $response = User::where('id', '=', $request['id'])->update([
                'password' => bcrypt($request->input('password'))
            ]);
            if ($response) {
                $res = array(
                    'status' => 200,
                    'message' => 'Updated'
                );

            } else {

                $res = array(
                    'status' => 400,
                    'message' => 'failed'
                );
            }
            return $res;
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function updateUserStatus(\Illuminate\Http\Request $request, $id)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $companyID = $request->user()->id;
            $model = AppUsers::where('row_id', $id)->first();
            if ($model->app_group_id == $appGroupID && $model->company_id == $companyID) {
                $response = AppUsers::where('row_id', '=', $id)->update([
                    'status' => $request['is_active']
                ]);
                if ($response) {
                    $res = array(
                        'status' => 200,
                        'message' => 'Updated'
                    );

                } else {

                    $res = array(
                        'status' => 400,
                        'message' => 'failed'
                    );
                }
                return $res;
            } else {
                throw new \Exception('Invalid user.');
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }

    }

    public function companies(\Illuminate\Http\Request $request)
    {
        $companies = User::with('roles', 'app_groups')->whereHas('roles', function ($q) {
            $q->where('name', 'COMPANY');
        })->get();

        return $companies;
    }

    public function destroy($request)
    {
        Artisan::call("company:remove", ['id' => $request->get('id')]);
    }

    public function rebuildCache(\Illuminate\Http\Request $request)
    {
        try {
            $id = $request->get('id');
            $company = User::find($id);
            $company->update([
                'cache_status' => 'inprocess'
            ]);

            RebuildCacheJob::dispatch($company)->onQueue('rebuild_cache')->delay(Carbon::now()->addSeconds(10));

            return response()->json([
                'status' => 200,
                'message' => 'Rebuild cache successfully.'
            ]);
        } catch (\Exception $exp) {
            return response()->json([
                'status' => 400,
                'message' => 'Failed'
            ], 401);
        }
    }

    public function removeUser($request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $companyID = $request->user()->id;
            if ($model->app_group_id != $appGroupID || $model->company_id != $companyID) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Invalid user.'],
                    'error'
                );
            }
            $user = AppUsers::with('tokens')->find($model->row_id);
            if (empty($user)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_NOT_FOUND,
                    'error',
                    ['Unable to load app user data'],
                    'meta'
                );
            }

            $cache = new CacheKeys($user->app_group_id);
            $cache_key = $cache->generateAppUserLoginSignupKey($user->row_id);

            \DB::beginTransaction();

            $user->tokens()->forceDelete();

            AttributeData::where('row_id', $user->row_id)->delete();
            AppUserActivity::where('row_id', $user->row_id)->delete();
            CampaignTracking::where('row_id', $user->row_id)->delete();
            LinkTrackings::where('row_id', $user->row_id)->forcedelete();
            NewsFeedImpression::where('row_id', $user->row_id)->forcedelete();

            $user->forceDelete();

            \DB::commit();

            AppUserLoginSignupCache::removeEntry($cache_key);

            return response()->json('App user deleted successfully.');
        } catch (\Exception $exception) {
            \DB::rollBack();

            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load app user data'],
                $exception->getMessage()
            );
        }
    }

    public function changeNotification($companyId, $appGroupId, $data)
    {
        try {
            $user = AppUsers::where('company_id', $companyId)
                ->where('app_group_id', $appGroupId)
                ->where('row_id', $data['userId'])
                ->first();

            if ($user) {
                $notification = $data['key'];
                $user->$notification = $data['value'];
                $user->save();

                $params['company_id'] = $companyId;
                $params['app_group_id'] = $appGroupId;

                $_user = AppUsers::find($data['userId']);
                $params['user_id'] = $_user->user_id;
                $params['app_id'] = $_user->app_id;

                if ($data['key'] == 'email_notification') {
                    $login_cache = new AppUserLoginSignupCache();
                    $login_cache->saveAppUserLoginCache($params);
                }

                if ($data['key'] == 'enable_notification') {
                    $login_cache = new AppUserLoginSignupCache();
                    $login_cache->saveAppUserLoginCache($params);
                }

                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    'notification value updated',
                    'data'
                );
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                'user not found',
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

    public function getPackageDetails($companyId)
    {
        try {

            // we will get $data from cache

            $packageInfo = DB::table("user_package_history")
                ->join("package", "user_package_history.package_id", "=", "package.id")
                ->where("user_package_history.is_active", 1)
                ->select("package.*", "user_package_history.start_time", "user_package_history.end_time")
                ->first();

            $data = [
                "name" => isset($packageInfo->name) ? $packageInfo->name : null,
                "type" => isset($packageInfo->type) ? $packageInfo->type : null,
                "startDate" => isset($packageInfo->start_time) ? $packageInfo->start_time : null,
                "endDate" => isset($packageInfo->end_time) ? $packageInfo->end_time : null,
                "details" => [
                    [
                        "feature" => "InApp",
                        "used" => 0,
                        "total" => $packageInfo->inapp_limit
                    ],
                    [
                        "feature" => "Push",
                        "used" => 0,
                        "total" => $packageInfo->push_limit
                    ],
                    [
                        "feature" => "Email",
                        "used" => 0,
                        "total" => $packageInfo->email_limit
                    ],
                    [
                        "feature" => "NFC",
                        "used" => 0,
                        "total" => $packageInfo->nfc_limit
                    ],
                    [
                        "feature" => "Attributes",
                        "used" => 0,
                        "total" => User::find($companyId)->attribute_limit
                    ]
                ]
            ];

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $data,
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

    public function getCompanySideFilters()
    {
        try {

            $filtersArr = [
                [
                    "column" => "Status",
                    "children" => [
                        [
                            "parent" => "is_active",
                            "value" => 1,
                            "label" => "Active"
                        ],
                        [
                            "parent" => "is_active",
                            "value" => 0,
                            "label" => "Inactive"
                        ]
                    ]
                ]
            ];

            $packages = Package::get();
            $obj = (object)[];
            $obj->column = "Package";
            $obj->children = [];

            foreach ($packages as $package) {
                $innerObj = (object)[];
                $innerObj->parent = "name";
                $innerObj->value = $package->name;
                $innerObj->label = $package->name;
                $obj->children[] = $innerObj;
            }

            $filtersArr[] = $obj;

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $filtersArr,
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

    public function getPackageListing($version, $companyId)
    {
        try {

            $subscriber = DB::table("user_package_history")
                ->join("package", "user_package_history.package_id", "=", "package.id")
                ->where("user_package_history.user_id", $companyId)
                ->where("user_package_history.is_active", 1)
                ->select("user_package_history.package_id", "package.type")
                ->first();

            $queryChain = DB::table("package")
                ->where("is_active", 1);

            if ($subscriber) {

                $queryChain->where("id", "<>", $subscriber->package_id);

                if (strtolower($subscriber->type) == "yearly") {
                    $queryChain->where("type", $subscriber->type);
                }
            }

            $data = $queryChain->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $data,
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

    public function changePackage($data)
    {

        try {

            $packageUsed = [
                "push" => 1,
                "inapp" => 1,
                "email" => 1,
                "nfc" => 1
            ];

            if ($data['selectedPackage']['push_limit'] >= $packageUsed['push'] && $data['selectedPackage']['inapp_limit'] >= $packageUsed['inapp'] && $data['selectedPackage']['email_limit'] >= $packageUsed['email'] && $data['selectedPackage']['nfc_limit'] >= $packageUsed['nfc']) {
                UserPackageHistory::where("user_id", $data["companyId"])
                    ->where("is_active", 1)
                    ->update([
                        "is_active" => 0,
                        "end_time" => new Carbon()
                    ]);

                $date = new Carbon();
                $userCurrentPackage = new UserPackageHistory();
                $userCurrentPackage->user_id = $data["companyId"];
                $userCurrentPackage->package_id = $data["selectedPackage"]["id"];
                $userCurrentPackage->start_time = clone $date;

                $months = 0;
                if (strtolower($data["selectedPackage"]["type"]) == "monthly") {
                    $months = 1;
                } else if (strtolower($data["selectedPackage"]["type"]) == "yearly") {
                    $months = 12;
                }

                $userCurrentPackage->end_time = $date->addMonthsNoOverflow($months);
                $userCurrentPackage->is_active = 1;
                $userCurrentPackage->save();


                //command for rebuild cache

                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'success',
                    "Package updated successfully",
                    'data'
                );
            }

            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                "This company cannot be shifted to this package",
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