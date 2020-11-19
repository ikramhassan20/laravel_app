<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\ResponseLog as ResponseTimeLog;
use App\Components\AppStatusCodes;

class ResponseTimeController extends Controller
{

    public function getResponseTime(Request $request)
    {
        try {

            $user = $request->user();
            $queryChain = ResponseTimeLog::query();

            if (!empty($request['sideFilters']) && $request['sideFilters'] == 'API') {
                $queryChain->where('company_id', $user->id);
                $queryChain->where('type', 'api');
            } else if (in_array($request['sideFilters'], ["campaign", "board", "other"])) {
                $queryChain->where('console_type', $request['sideFilters']);
            } else {
                $queryChain->where('type', 'console');
            }

            if ($request['query'] != null) {
                $search = $request['query'];
                $queryChain->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%{$search}%");
                    $query->orWhere('company_id', 'LIKE', "%{$search}%");
                    $query->orWhere('name', 'LIKE', "%{$search}%");
                    $query->orWhere('type', 'LIKE', "%{$search}%");
                    $query->orWhere('response_time', 'LIKE', "%{$search}%");
                    $query->orWhere('created_at', 'LIKE', "%{$search}%");
                });
            }

            $totalFiltered = clone $queryChain;
            $totalFiltered = $totalFiltered->count();
            isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('created_at', 'desc');

            $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
                ->limit($request['limit'])
                ->get();

            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];

            $response = [
                'meta' => $meta,
                'data' => $data
            ];

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
                [$exception->getMessage()],
                'error'
            );
        }

    } // end of function

    public function logDelete(Request $request)
    {
        if (!empty($request['id'])) {
            ResponseTimeLog::where('id', $request['id'])->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Log deleted successfully.'
            ]);
        }

    }

} // end of class