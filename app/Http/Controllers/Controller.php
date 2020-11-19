<?php

namespace App\Http\Controllers;

use App\Components\ParseResponse;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    protected $resourceClass;

    use AuthorizesRequests, DispatchesJobs, ParseResponse, ValidatesRequests;

    /**
     * @param string $resource
     * @return \Illuminate\Http\JsonResponse
     */
    protected function setResourceClass($resource)
    {
        $class = "\\App\\Http\\Resources\\" . strtoupper(request()->segment(2)) . "\\" . studly_case($resource) . "Resource";
        if (!class_exists($class)) {
            return $this->addResponse(
                404,
                'error',
                ['Cannot locate ' . $resource . ' resource class'],
                'error'
            );
        }
        $this->resourceClass = (new $class);
    }
}
