<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 1/23/19
 * Time: 5:29 PM
 */

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Components\RandomString;
use App\Components\RenderLocationPaginateResponse;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\ResourcesSteps;
use App\Location;
use App\LocationArea;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class LocationResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps;

    public function create(\Illuminate\Http\Request $request)
    {
        try {
            $user = $request->user();
            $group = $user->currentAppGroup();
            $errors = (new RenderLocationPaginateResponse())->Lookupvalidator($request);
            if ($errors) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    'Location already exist',
                    'error'
                );
            }
            $loginId = Auth::user()->id;
            $companyId = $request->user()->id;
            if ($companyId != $loginId) {
                throw new \Exception('Invalid User');
            }
            $response = $this->process($request, (new Location()));
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create location entry'],
                $exception->getMessage()
            );
        }

    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $appGroupID = $request->user()->currentAppGroup()->id;
            $locationResponse = Location::where('id', '=', $request->id)->first();
            if (!empty($locationResponse)) {
                if ($locationResponse->app_group_id != $appGroupID) {
                    throw new \Exception('Invalid User');
                } else {
                    $locationResponse->update(['is_active' => $request->is_active]);

                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        AppStatusMessages::SUCCESS,
                        $locationResponse,
                        'data'
                    );
                }
            } else {
                throw new \Exception('Data not found');
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

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $loginId = Auth::user()->id;
            $companyId = $request->user()->id;
            if ($companyId != $loginId) {
                throw new \Exception('Invalid User');
            }
            $response = (new RenderLocationPaginateResponse())->renderpaginate($request);
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
                [$exception->getMessage()],
                'error'
            );
        }
    }


    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
        $data = $this->parseResponse($request);
        $data['updated_by'] = $user->id;
        if ($data['id'] == null) {
            $group = $user->currentAppGroup();
            $model->app_group_id = $group->id;
            $model->code = strtoupper(RandomString::generateWithPrefix('location'));
            $model->name = $data['name'];
            $model->description = $data['description'];
            $model->is_active = 1;
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
            $model->save();
        } else {
            $model = $model->where('id', $data['id'])->first();
            if (!empty($model)) {
                if ($model->app_group_id != $group->id) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                        'error',
                        ['Invalid user.'],
                        'error'
                    );
                } else {
                    $model->update($data);
                }
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Data not found.'],
                    'error'
                );
            }
        }
        return $model;
    }

    public function areaStore(\Illuminate\Http\Request $request)
    {
        try {
            $loginId = Auth::user()->id;
            $companyId = $request->user()->id;
            if ($companyId != $loginId) {
                throw new \Exception('Invalid User');
            }
            $response = $this->LocationProcess($request, (new LocationArea()));
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create location entry'],
                $exception->getMessage()
            );
        }
    }

    public function LocationProcess($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $data = $this->parseResponse($request);
        $loginId = Auth::user()->id;
        $companyId = $request->user()->id;
        if ($companyId != $loginId) {
            throw new \Exception('Invalid User');
        }
        //dd($data);
        if ($data['id'] == '') {

            $model->location_id = $data['location_id'];
            $model->address = $data['default_name'];
            $model->latitude = $data['lat'];
            $model->longitude = $data['lng'];
            $model->radius = $data['radius'];
            $model = $model->save();

        } else {
            $model->where('id', '=', $data['id'])->update([
                'address' => $data['default_name'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'radius' => $data['radius']
            ]);
        }
        $model = LocationArea::where('location_id', $data['location_id'])->get();
        return $model;
    }

    public function areaDelete($request)
    {
        try {
            $user = $request->user();
            $loginId = Auth::user()->id;
            $companyId = $request->user()->id;
            if ($companyId != $loginId) {
                throw new \Exception('Invalid User');
            }
            $data = $this->parseResponse($request);

            $locationAreaDelete = LocationArea::where('id', $data['id'])->update([
                'deleted_at' => Carbon::now()
            ]);
            if ($locationAreaDelete) {
                $locationArea = LocationArea::where('location_id', $data['location_id'])->get();
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    $locationArea,
                    'data'
                );

            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Unable to create location entry'],
                    'error'
                );
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create location entry'],
                $exception->getMessage()
            );
        }

    }

    public function show(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function editlocation($id)
    {
        try {
            $locationArray = Location::with('getlocationArea')
                ->where('id', $id)
                ->get();
            $appGroupId = $locationArray['0']->app_group_id;
            $group = Auth::user()->currentAppGroup();
            if ($group->id != $appGroupId) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_METHOD_NOT_ALLOWED,
                    'error',
                    'User Not Valid',
                    'error'
                );
            }
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $locationArray,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                $exception->getMessage(),
                $exception->getMessage()
            );
        }
    }

    public function removeLocation($request, \Illuminate\Database\Eloquent\Model $model)
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