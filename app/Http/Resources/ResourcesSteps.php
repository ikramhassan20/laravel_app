<?php

namespace App\Http\Resources;

use App\Components\AppStatusCodes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResourcesSteps
{
    /**
     * Return data for a resource.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Model $model)
    {
        return $this->addResponse(
            AppStatusCodes::HTTP_OK,
            'success',
            $model,
            'data'
        );
    }

    /**
     * Update data for a resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Model $model)
    {
        $resource = $this->resourceName(
            get_class($model)
        );

        try {
            $data = $this->parseResponse($request);

            $model = $this->process($data, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $model,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ["Unable to update " . strtolower($resource)],
                'error'
            );
        }
    }

    /**
     * Remove a resource.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Model $model)
    {
        try {

            if(get_class($model) == 'App\Segment'){
                // remove segment cache
                $model = $this->removeSegmentCache($model);
            }

            $model->delete();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                ["Resource has been removed successfully"],
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ["Unable to remove resource"],
                'error'
            );
        }
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function resourceName($class)
    {
        return array_pop(
            explode("\\", $class)
        );
    }
}