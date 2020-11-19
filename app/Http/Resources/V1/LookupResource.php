<?php

namespace App\Http\Resources\V1;

use App\Components\ParseResponse;
use App\Components\RenderPaginatedResponse;
use App\Http\Resources\ResourcesSteps;
use App\Lookup;
use Illuminate\Http\Request;
use App\Components\AppStatusCodes;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Http\Resources\Contracts\ResourcesContract;

class LookupResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderPaginatedResponse;

    public function all(Request $request)
    {
        try {
            $response = (new Lookups\PaginateLookupResponse())->process($request);

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
                ['Unable to load Lookup data'],
                'error'
            );
        }
    }

    public function edit($id)
    {
        try {
            $response = (new Lookups\PaginateLookupResponse())->getLookupbyId($id);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load Lookup data'],
                'error'
            );
        }
    }


    public function create(Request $request)
    {
        try {
            $errors = (new Lookups\LookupCodeValidator())->process($request);
            if ($errors) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    'code already exist',
                    'error'
                );
            }

            $lookup = $this->process($request, (new Lookup()));

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $lookup,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create lookup entry'],
                $exception->getMessage()
            );
        }
    }

    public function filters(Request $request)
    {

        try {
            $response = (new Lookups\LookupFilters())->process($request);

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
                ['Unable to load Lookup data'],
                'error'
            );
        }
    }


    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $data = $this->parseResponse($request);
        $data['updated_by'] = $user->id;
        if (!isset($model->id)) {
            unset($data['lookupid']);
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $data['level'] = 'platform';
            $data['app_group_id'] = 1;
            $model->create($data);
        } else {
            $model->update($data);
        }
        return $model;
    }


    public function update(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
//            $errors = (new Lookups\LookupCodeValidator())->process($request);
//            if ($errors) {
//                return $this->addResponse(
//                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
//                    'error',
//                    'code already exist',
//                    'error'
//                );
//            }

            $lookup = $this->process($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $lookup,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create lookup entry'],
                $exception->getMessage()
            );
        }
    }

    public function delete(Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $checkChildLookUps = Lookup::where('parent_id', $model->id)->first();
            if (empty($checkChildLookUps)) {
                $model->delete();
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'success',
                    [],
                    'data'
                );
            } else {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    'This lookup has child lookups so cannot be deleted.',
                    'error'
                );
            }
        } catch (\Exception $e) {

        }

    }
}
