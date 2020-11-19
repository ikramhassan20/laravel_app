<?php

namespace App\Components;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;

trait RenderPaginatedResponse
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function paginateResponse($model, Request $request)
    {
        $params = $this->parseResponse($request);

        $total = $model::count();
        $perPage = !empty($params['limit']) ? $params['limit'] : config('engagement.limit');
        $pages = ceil($total/$perPage);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $skip = ($page > 1) ? (($page-1) * $perPage) : 0;

        $where = !empty($params['query']) ? $params['query'] : '';
        $sortColumn = !empty($params['orderBy']) ? strtolower($params['orderBy']) : '';
        $sortOrder = '';
        if (!empty($params['ascending'])) {
            $sortOrder = ((int)$params['ascending'] === 0) ? 'asc' : 'desc';
        }

        $meta = [
            'pages' => $pages,
            'page'  => $page,
            'total' => $total,
        ];

        if (!empty($where)) {
            $data = $model::whereRaw("name LIKE '%".addslashes($where)."%'");
            if (!empty($sortOrder) && !empty($sortColumn)) {
                $data = $data->skip($skip)->take($perPage)->orderBy($sortColumn, $sortOrder);
            }
        } else {
            if (!empty($sortOrder) && !empty($sortColumn)) {
                $data = $model::skip($skip)->take($perPage)->orderBy($sortColumn, $sortOrder);
            } else {
                $data = $model::skip($skip)->take($perPage);
            }
        }

        $data = $data->get();

        return [
            'meta' => $meta,
            'data' => $data
        ];
    }
}