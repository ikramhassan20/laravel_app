<?php

namespace App\Http\Resources\Contracts;

interface ProcessResourceDataContract
{
    /**
     * Process resource data.
     *
     * @param array|\Illuminate\Http\Request      $request
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return array|\Illuminate\Database\Eloquent\Model
     */
    public function process($request, \Illuminate\Database\Eloquent\Model $model);
}