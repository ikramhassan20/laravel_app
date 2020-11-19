<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class ApiResponseTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $isLog = config('engagement.api.log_api_response_time');
        if($isLog){
            $response_time = (microtime(true) - LARAVEL_START)*1000;
            \App\ResponseLog::logData([
                "company_id" => $request->user()->id,
                "name" => $request->path(),
                "type" => 'api',
                "response_time" => $response_time
            ]);
        }

        return ($response);
    }
}
