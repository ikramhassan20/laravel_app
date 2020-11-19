<?php

namespace App\Http\Resources\V1\Lookups;

use App\Components\ParseResponse;
use App\Lookup;
use Illuminate\Http\Request;

class LookupCodeValidator
{
    use ParseResponse;

    /**
     * @param \Illuminate\Http\Request            $request
     * @param \Illuminate\Database\Eloquent\Model $lookup
     *
     * @return array
     */
    public function process(Request $request)
    {
        $result=Lookup::where('code','=',$request->code)->get();
        if(count($result)>0)
        {
            $trueflag=true;
        }else{
            $trueflag=false;
        }
        return $trueflag;
    }
}
