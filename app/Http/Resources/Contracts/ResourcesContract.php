<?php

namespace App\Http\Resources\Contracts;

interface ResourcesContract
{
    /**
     * Get items list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(\Illuminate\Http\Request $request);

    /**
     * Create a new resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(\Illuminate\Http\Request $request);

    /**
     * Return data for a segment.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(\Illuminate\Database\Eloquent\Model $model);

    /**
     * Update data for a resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model);

    /**
     * Remove a resource.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(\Illuminate\Database\Eloquent\Model $model);
}