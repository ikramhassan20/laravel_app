<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class CommandController extends Controller
{
    public function commandsCache(Request $request)
    {
        $groupId = $request->get('group_id');

        $commands = [];

        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_row_id_ID');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_segments');
        array_push($commands, \Config::get('cache.prefix') . ':campaign_ID_segments');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_campaign_id_ID_segments_union');
        array_push($commands, \Config::get('cache.prefix') . ':board_ID_segments');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_once_board_id_ID_rows');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_board_id_ID_segments_union');
        array_push($commands, \Config::get('cache.prefix') . ':board_user_tracking_board_id_ID_row_id_ID');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_board_ID_language_ID_variant_ID');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_segment_ID_rows');
        array_push($commands, \Config::get('cache.prefix') . ':campaign_tracking_campaign_id_ID_row_id_ID_language_CODE_variant_ID');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_campaign_ID_language_ID_variant_ID');
        array_push($commands, \Config::get('cache.prefix') . ':app_group_id_' . $groupId . '_board_ID_language_ID_variant_ID');

        return response()->json($commands);
    }

    /*public function commandData(Request $request)
    {
        $command = $request->get('command');

        $key = str_replace(\Config::get('cache.prefix') . ':', '', $command);
        if (\Cache::has($key)) {
            $data = \Cache::get($key);

            if (empty($data)) {
                return response()->json('No data found.');
            }

            return response()->json($data);
        }

        return response()->json('No data found.');
    }*/

    public function commandData(Request $request)
    {
        $command = $request->get('command');
        $key = str_replace(\Config::get('cache.prefix') . ':', '', $command);

        $isKey = (bool)Redis::exists($command);
        if ($isKey) {
            $cacheType = Redis::type($command);
            if($cacheType == "set"){
                $data = Redis::smembers($command);
                sort($data);
                $data = json_encode($data, JSON_NUMERIC_CHECK);
            }
            elseif($cacheType == "string"){
                $data = \Cache::get($key);
            }

            if (empty($data)) {
                return response()->json('No data found.');
            }

            return response()->json($data);
        }

        return response()->json('No data found.');
    }

}
