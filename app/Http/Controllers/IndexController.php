<?php

namespace App\Http\Controllers;

use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    //
    public function index(Request $request)
    {
        return view('welcome');
    }

    public function getServerTime(Request $request)
    {
        $serverTime = Carbon::now()->format('Y-m-d H:i:s');

        $databaseTime = DB::select('SELECT NOW() as databaseTime');

        $data = [
            'serverTime' => $serverTime,
            'databaseTime' => (!empty($databaseTime) && !empty($databaseTime[0])) ? $databaseTime[0]->databaseTime : ''
        ];

        return $this->addResponse(
            AppStatusCodes::HTTP_OK,
            AppStatusMessages::SUCCESS,
            $data,
            'data'
        );


    }
}
