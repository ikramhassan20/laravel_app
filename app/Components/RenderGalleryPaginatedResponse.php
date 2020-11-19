<?php

namespace App\Components;

use App\Gallery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait RenderGalleryPaginatedResponse
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */

    public function GalleryPaginateResponse($model, Request $request)
    {
        $queryChain = Gallery::where('company_id', $request->user()->id)->where('deleted_at', NULL);
        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] =='0' || $request['sideFilters'] =='1') {
            $queryChain->where('gallery.is_active','=',$request['sideFilters']);
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $columns = $request['columns'];
            $queryChain->where(function ($query) use ($search, $columns) {
                $query->where('gallery.id', 'LIKE', "%{$search}%");
                $query->orWhere('gallery.image_name', 'LIKE', "%{$search}%");
                $query->orWhere('gallery.image_width', 'LIKE', "%{$search}%");
                $query->orWhere('gallery.image_height', 'LIKE', "%{$search}%");
                $query->orWhere('gallery.image_size', 'LIKE', "%{$search}%");
                $query->orWhere('gallery.created_at', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        $queryChain->orderBy('gallery.id', $request["ascending"] == 1 ? 'desc' : 'asc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            ->get();
        for ($val = 0; $val < count($data); $val++) {
            if ($data[$val]['is_active'] == 1) {
                $data[$val]['is_active'] = 'Active';
                $data[$val]['status'] = 1;
            } else {
                $data[$val]['is_active'] = 'InActive';
                $data[$val]['status'] = 0;
            }
        }

        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];
        return [
            'meta' => $meta,
            'data' => $data
        ];
    }

}