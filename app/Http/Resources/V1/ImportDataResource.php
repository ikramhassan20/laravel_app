<?php

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use App\Components\RenderLocationPaginateResponse;
use App\Http\Resources\ResourcesSteps;
use App\ImportData;

class ImportDataResource
{
    use ParseResponse, ResourcesSteps;

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $attr = $request->all();
            $user = $request->user();

            $imports = ImportData::where('company_id', $user->id)->orderBy('id', 'DESC');

            if(isset($attr['query']) AND $attr['query']['status']) {
                $imports = $imports->where('status', $attr['query']['status']);
            }

            $imports = $imports->get();
            $totalFiltered = count($imports);

            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $imports,
                'data',
                $meta
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
}