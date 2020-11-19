<?php

namespace App\Concerns;

use App\Cache\BoardSegmentCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Cache\AppGroupSegmentCache;
use App\Cache\CampaignSegmentCache;
use File;
use Illuminate\Support\Facades\Storage;


trait exportUsers
{
    public static function exportUsers($id, $type, $appGroupId, $count = false)
    {
        $rowIds = exportUsers::uniqueRowIds($id, $type, $appGroupId);

        if ($count) {
            return sizeof($rowIds);
        }

        $company = Auth::user();
        $files = File::cleanDirectory(storage_path('app/public/company_' . $company->id . '/export/'));

        $headers = exportUsers::getHeaders($appGroupId);
        // adding is_revoked and is_logged_in columns for export
        array_push($headers, 'is_revoked');

        sort($rowIds);
        sort($headers);

        $fileName = $type . '_' . $id . '_app_users_data_' . Carbon::now()->timestamp;
        $filePath = 'public/company_' . $company->id . '/export/' . $fileName . '.csv';

        $disk = Storage::disk('local');
        $disk->put($filePath, implode(",", $headers));

        $chunkSize = config('engagement.api.export.chunk_size');
        $rowIdsChunks = array_chunk($rowIds, $chunkSize);
        foreach ($rowIdsChunks as $rowIdsChunk) {
            $disk->append($filePath, rtrim(exportUsers::getUsers($rowIdsChunk, $headers, true)));
        }

        return ['company_id' => $company->id, 'file_name' => $fileName];

    }

    public static function uniqueRowIds($id, $type, $app_group_id)
    {
        $segmentIds = [];
        $segmentIds[] = $id;
        if ($type == "campaign") {
            $campaignSegments = new CampaignSegmentCache();
            $segmentIds = $campaignSegments->getCampaignSegments($id);
        }
        if ($type == "board") {
            $boardSegments = new BoardSegmentCache();
            $segmentIds = $boardSegments->getBoardSegmentsCache($id);
        }
        $rowIds = [];
        $segmentCache = new AppGroupSegmentCache();
        foreach ($segmentIds as $segmentId) {
            $segmentRowIds = $segmentCache->getAppGroupSegmentRowsCache($app_group_id, $segmentId); //getAppGroupSegmentRowsCache($segmentId);
            if ($segmentRowIds == null) {
                $segmentRowIds = [];
            }
            $rowIds = array_unique(array_merge($rowIds, $segmentRowIds));
        }

        return $rowIds;
    }

    public static function getHeaders($appGroupId)
    {
        $queryString = 'SELECT DISTINCT code FROM attribute as a1 ';
        $queryString .= 'WHERE (a1.deleted_at is null && (a1.app_group_id = ' . $appGroupId . ' OR a1.level_type = "platform")) AND Not EXISTS ( ';
        $queryString .= 'SELECT * ';
        $queryString .= 'FROM attribute as a2 ';
        $queryString .= 'WHERE a2.level_type = "platform" AND a1.code = a2.code AND a1.level_type != a2.level_type )';
        return collect(DB::Select($queryString))->pluck('code')->toArray();
    }

    public static function getUsers($rowIds, $headers, $isExport = false)
    {
        $string = '';

        $appUsers = exportUsers::getAppUsers($rowIds, $headers);
        $records = exportUsers::getUsersAttributes($rowIds);
        $currentRecordId = isset($records[0]->row_id) ? $records[0]->row_id : 0;
        $user = [];

        foreach ($records as $record) {
            if ($record->row_id != $currentRecordId) {
                $currentRecordId = $record->row_id;
                $user['row_id'] = $record->row_id;
                exportUsers::arrayMerge($appUsers, $user);
                $user = [];
            }
            if (!empty($user[$record->code])) {
                if (!empty($record->value)) {
                    if ($isExport == true) {
                        $user[$record->code] = $user[$record->code] . '|' . $record->value;
                    } else {
                        $user[$record->code] = $user[$record->code] . ',' . $record->value;
                    }
                }
            } else {
                $user[$record->code] = $record->value;
            }
        }

        if (!empty($records)) {
            $user['row_id'] = $records[sizeof($records) - 1]->row_id;
            exportUsers::arrayMerge($appUsers, $user);
        }

        if ($headers != false) {
            foreach ($appUsers as $appUser) {
                $string .= exportUsers::syncAndConvertToString($appUser, $headers);
            }
            return $string;
        }

        return (!empty($appUsers[0])) ? $appUsers[0] : [];
    }

    public static function syncAndConvertToString($user, $headers)
    {

        foreach ($user as $key => $value) {
            if (!in_array($key, $headers)) {
                unset($user[$key]);
            }
        }

        foreach ($headers as $column) {
            if (!isset($user[$column])) {
                $user[$column] = "N/A";
            }
        }

        ksort($user);
        return implode(",", $user) . PHP_EOL;
    }

    public static function arrayMerge(&$appUsers, $user)
    {
        for ($i = 0; $i < sizeof($appUsers); $i++) {
            if ($appUsers[$i]['row_id'] == $user['row_id']) {
                $appUsers[$i] = array_merge($user, $appUsers[$i]);
                break;
            }
        }
    }

    public static function getAppUsers($rowIds, $headers)
    {
        $appUsers = DB::table('app_user as au1')
            ->leftJoin('app_user_token as aut1', 'au1.row_id', '=', 'aut1.row_id')
            ->whereIn("au1.row_id", $rowIds);
        $appUsers = $appUsers->orderBy("au1.row_id")
            ->get()->toArray();

        $appUsers = json_decode(\GuzzleHttp\json_encode($appUsers), true);

        $currentRowId = 0;
        $usersArray = [];
        $currentUser = [];

        foreach ($appUsers as $user) {

            if ($currentRowId != $user['row_id']) {

                if (!empty($currentUser)) {
                    $usersArray[] = $currentUser;
                }

                $currentUser = $user;
                $currentRowId = $user['row_id'];

            } else {
                $currentUser['app_name'] = rtrim(!empty($currentUser['app_name']) ? $currentUser['app_name'] . '/' . $user['app_name'] : $user['app_name'], '/');
                $currentUser['app_version'] = rtrim(!empty($currentUser['app_version']) ? $currentUser['app_version'] . '/' . $user['app_version'] : $user['app_version'], '/');
                $currentUser['app_build'] = rtrim(!empty($currentUser['app_build']) ? $currentUser['app_build'] . '/' . $user['app_build'] : $user['app_build'], '/');
                $currentUser['instance_id'] = rtrim(!empty($currentUser['instance_id']) ? $currentUser['instance_id'] . '/' . $user['instance_id'] : $user['instance_id'], '/');
                $currentUser['user_token'] = rtrim(!empty($currentUser['user_token']) ? $currentUser['user_token'] . '/' . $user['user_token'] : $user['user_token'], '/');
                $currentUser['device_token'] = rtrim(!empty($currentUser['device_token']) ? $currentUser['device_token'] . '/' . $user['device_token'] : $user['device_token'], '/');
                $currentUser['device_type'] = rtrim(!empty($currentUser['device_type']) ? $currentUser['device_type'] . '/' . $user['device_type'] : $user['device_type'], '/');
                $currentUser['lang'] = rtrim(!empty($currentUser['lang']) ? $currentUser['lang'] . '/' . $user['lang'] : $user['lang'], '/');
                $currentUser['is_logged_in'] = stripslashes(rtrim(!empty($currentUser['is_logged_in']) ? $currentUser['is_logged_in'] . '|' . $user['is_logged_in'] : $user['is_logged_in'], '/'));
                $currentUser['is_revoked'] = rtrim(!empty($currentUser['is_revoked']) ? $currentUser['is_revoked'] . '|' . $user['is_revoked'] : $user['is_revoked'], '/');
                $currentUser['status'] = rtrim(!empty($currentUser['status']) ? $currentUser['status'] . '/' . $user['status'] : $user['status'], '/');
                $currentUser['created_at'] = rtrim(!empty($currentUser['created_at']) ? $currentUser['created_at'] . '/' . $user['created_at'] : $user['created_at'], '/');
                $currentUser['deleted_at'] = rtrim(!empty($currentUser['deleted_at']) ? $currentUser['deleted_at'] . '/' . $user['deleted_at'] : $user['deleted_at'], '/');
            }
        }

        if (!empty($currentUser)) {
            $usersArray[] = $currentUser;
        }

        return $usersArray;
    }

    public static function getUsersAttributes($rowIds)
    {
        return DB::table("attribute_data")
            ->whereIn("row_id", $rowIds)
            ->where("data_type", "user")
            ->orderBy("row_id")
            ->select("row_id", "code", "value")
            ->get()->toArray();
    }

}