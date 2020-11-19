<?php

namespace App\Http\Middleware;

use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use Closure;

class ApiVersionMiddleware
{
    use ParseResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->segment(2), config('engagement.api.versions'))) {
            $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Invalid API version number'],
                'error'
            );
        }

        return $next($request);
    }
}
