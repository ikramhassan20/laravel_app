<?php

namespace App\Http\Resources\V1\Lookups;

use App\Lookup;
use Illuminate\Http\Request;

class LookupFilters
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function process(Request $request)
    {

        $finalArray = [];
        $mainObject = (object)[];
        $mainObject->columns = 'parent_id';
        $mainObject->columnsAlias = 'Type';
        $mainObject->childern = [];


        $obj = (object)[];
        $obj->parent = 'parent_id';
        $obj->value = 0;
        $obj->parentAlias = 'Parents Only';
        $mainObject->childern[] = $obj;

        $obj = (object)[];
        $obj->parent = 'parent_id';
        $obj->value = 1;
        $obj->parentAlias = 'Show all';
        $mainObject->childern[] = $obj;

        $finalArray['sideFilters'][] = $mainObject;

        $lookupFilters = Lookup::select(['id', 'name'])->where('parent_id', '=', 0)->get();

        $finalArray['parentLookups'] = $lookupFilters->toArray();

        $meta = [
            'status' => '200',
            'message' => 'Filters Found'
        ];

        return [
            'data' => $finalArray,
            'meta' => $meta
        ];
    }
}
