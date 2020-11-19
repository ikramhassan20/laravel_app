<?php

namespace App\Http\Resources\V1;

use App\App;
use App\AppGroup;
use App\Apps;
use App\AppUsers;
use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\DatabaseFactory;
use App\Components\ParseResponse;
use App\Components\RenderPaginatedResponse;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppGroupsResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderPaginatedResponse;

    /**
     * Get list of campaigns.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            // $response = $this->paginateResponse(AppGroup::class, $request);
            $response = AppGroup::where('company_id', $companyId)->get();
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load campaign data'],
                'error'
            );
        }
    }

    public function applist(Request $request)
    {
        try {
            $response = $this->appListprocess($request);
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
                ['Unable to load campaign data'],
                'error'
            );
        }
    }

    public function appListprocess(Request $request)
    {
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            throw new \Exception('Invalid User');
        }
        $user = $request->user();
        $group = $user->currentAppGroup();
        $queryChain = Apps::where('deleted_at', NULL);
        if ($user->is_admin == 0) {
            $queryChain = $queryChain->where('app_group_id', $group->id);
        }

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
            if ($request['sideFilters']['value'] == 'Active') {
                $queryChain->where($request['sideFilters']['parent'], '=', $request['sideFilters']['subVal']);
            } else if ($request['sideFilters']['value'] == 'InActive') {
                $queryChain->where($request['sideFilters']['parent'], '=', $request['sideFilters']['subVal']);
            } else {
                $queryChain->where($request['sideFilters']['parent'], '=', strtolower($request['sideFilters']['value']));
            }
        }

        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where('app.id', 'LIKE', "%{$search}%")
                    ->orWhere('app.name', 'LIKE', "%{$search}%")
                    ->orWhere('app.app_id', 'LIKE', "%{$search}%")
                    ->orWhere('app.description', 'LIKE', "%{$search}%")
                    ->orWhere('app.description', 'LIKE', "%{$search}%")
                    ->orWhere('app.platform', 'LIKE', "%{$search}%")
                    ->orWhere('app.created_at', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : '';
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->get();
        for ($val = 0; $val < count($data); $val++) {
            if ($data[$val]['is_active'] == '1') {
                $data[$val]['status'] = 'Active';
            } else {
                $data[$val]['status'] = 'In Active';
            }
        }
        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];
        return [
            'meta' => $meta,
            'data' => $data
        ];

    }

    /**
     * Create a new app group.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function create(Request $request)
    {
        try {
            $group = $this->process($request, new AppGroup());

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $group,
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

    /**
     * Process app groups data.
     *
     * @param array|\Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     *
     */

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $res = 0;
        $user = $request->user();
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            throw new \Exception('Invalid user.');
        }
        $data = $this->parseResponse($request);
        if ($data['id'] == 0) {
            $model->company_id = $user->id;
            $model->code = bin2hex(random_bytes(32));
            $model->name = $data['name'];
            $model->description = $data['description'];
            $model->logo = $data['logo'];
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
            $model = $model->save();
        } else {
            $checkAppGroup = AppGroup::where('id', '=', $data['id'])->first();
            if (!empty($checkAppGroup)) {
                if ($checkAppGroup->company_id != $companyId) {
                    throw new \Exception('Invalid user.');
                }
                $checkAppGroup->update([
                    'description' => $data['description'],
                    'logo' => $data['logo'],
                    'name' => $data['name']
                ]);
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['App group not found'],
                    'error'
                );
            }
        }

        $group = AppGroup::where('company_id', $user->id)->get();
        return $group;

    }

    public function saveApp(Request $request)
    {
        try {
            $group = $this->saveAppProcess($request, new Apps());

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $group,
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

    public function saveAppProcess(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
        $companyId = $request->user()->id;
        $id = Auth::user()->id;
        if ($companyId != $id) {
            throw new \Exception('Invalid User');
        }
        $data = $this->parseResponse($request);
        if ($data['id'] == 0) {
            $appUserCheck = $model->join('app_group', 'app_group.id', '=', 'app.app_group_id')
                ->where('app_group.company_id', $user->id)
                ->where('app.name', '=', $data['name'])
                ->where('app.app_id', '=', $data['app_id'])
                ->where('app.platform', '=', $data['platform'])
                ->first();
            if (!$appUserCheck) {
                $group = $user->currentAppGroup();
                $model->name = $data['name'];
                $model->app_group_id = $group->id;
                $model->name = $data['name'];
                $model->logo = $data['logo'];
                $model->app_id = $data['app_id'];
                $model->description = $data['description'];
                $model->firebase_api_key = $data['firebase_api_key'];
                $model->platform = $data['platform'];
                $model->is_active = $data['is_active'];
                $model = $model->save();
            } else {
                throw new \Exception('App already exist for the current app group');
            }
        } else {
            $appUserCheck = $model->where('id', '=', $data['id'])
                ->where('app_group_id', '=', $group->id)->first();
            if ($appUserCheck) {
                $model = $model->where('id', '=', $data['id'])
                    ->update($data);
            } else {
                throw new \Exception('Select current app group for this app');
            }
        }

        return $model;
    }

    public function editApp(Request $request, $id)
    {
        try {
            $companyId = $request->user()->id;
            $id = Auth::user()->id;
            if ($companyId != $id) {
                throw new \Exception('Invalid User');
            }
            $response = Apps::where('id', '=', $id)->first();
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data',
                []
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load app data'],
                'error'
            );
        }
    }

    public function appUpdate(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $group = $this->saveAppProcess($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $group,
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

    /**
     * Update data for a app group.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $group = $this->process($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $group,
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

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function setCurrentAppGroup(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            (new AppGroups\UnsetCurrentAppGroups())->process($request);

            $model->update([
                'is_default' => true
            ]);
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                [$model->name . ' has been set as current app group'],
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$model->name . ' cannot be set as current app group'],
                $exception->getMessage()
            );
        }
    }

    public function statusUpdate(Request $request)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
        $data = $this->parseResponse($request);
        try {
            $appGroupCheck = Apps::where('id', '=', $data['id'])->first();
            if (!empty($appGroupCheck)) {
                if ($appGroupCheck->app_group_id != $group->id) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                        'error',
                        ['Invalid user.'],
                        'error'
                    );
                } else {
                    $appGroupCheck->update([
                        'is_active' => $data['is_active']
                    ]);
                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        AppStatusMessages::SUCCESS,
                        $appGroupCheck,
                        'data',
                        []
                    );
                }
            } else {
                throw new \Exception('Data not found.');
            }

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to Company data'],
                $exception->getMessage()
            );
        }

    }

    public function removeAppResource($request, \Illuminate\Database\Eloquent\Model $model)
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
