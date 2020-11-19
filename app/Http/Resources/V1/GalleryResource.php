<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 2/7/19
 * Time: 2:53 PM
 */

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\RenderGalleryPaginatedResponse;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Gallery;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\Contracts\ProcessResourceDataContract;

class GalleryResource implements ResourcesContract, ProcessResourceDataContract
{
    use ParseResponse, ResourcesSteps, RenderGalleryPaginatedResponse;

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $response = $this->GalleryPaginateResponse(Gallery::class, $request);
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
                ['Unable to gallery data'],
                'error'
            );
        }
    }


    public function create(\Illuminate\Http\Request $request)
    {
        try {
            $language = $this->process($request, new Gallery());
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $language,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create gallery'],
                $exception->getMessage()
            );
        }

    }

    public function show(\Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {

    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $data = $this->parseResponse($request);
        if (!isset($model->id)) {
            $data['company_id'] = $user->id;
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $model->create($data);
        } else {
            $model->update($data);
        }
        return $model;
    }

    public function removeGallery($request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $gallery = Gallery::where('id', '=', $model->id)->where('company_id', '=', $request->user()->id)->get();
            if (count($gallery) > 0) {
                if (empty($gallery)) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                        'error',
                        ['Gallery not found'],
                        'Gallery not found'
                    );
                }
                Gallery::where('id', '=', $model->id)->where('company_id', '=', $request->user()->id)->delete();
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    AppStatusMessages::SUCCESS,
                    $gallery,
                    'data'
                );
            } else {
                throw new \Exception('Invalid User');
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
}