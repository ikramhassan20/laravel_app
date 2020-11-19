<?php

namespace App\Components;

use Illuminate\Http\Request;

trait ParseResponse
{
    /**
     * @param int    $code
     * @param string $status
     * @param array  $data
     * @param string $key
     * @param array  $meta
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addResponse($code, $status, $data, $key, $meta = [])
    {
        return response()->json([
            'meta' => array_merge($meta, [
                'status'    => $status,
                'code'      => $code
            ]),
            $key => $data
        ], $code);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function parseResponse(Request $request)
    {
        return $request->isJson() ? $request->json()->all() : $request->all();
    }
}
