<?php

namespace App\Components;

use App\AttributeData;
use App\EmailList;
use App\Helpers\CommonHelper;
use App\UserAttribute;
use Illuminate\Support\Facades\DB;

class CompanyAttributeData
{
    /**
     * Regenerate cache data for a row.
     *
     * @param int $company_id
     * @param int $row_id
     *
     * @return bool
     */
    public static function updateRow($company_id, $row_id)
    {
        $rows = [];
        $details = [];

        try {
            $cache_key = "company_" . $company_id . "_rows";
            $row_ids = \Cache::get($cache_key);

            if (!empty($row_ids)) {
                $row_ids = \GuzzleHttp\json_decode($row_ids, true);
            } else {
                $row_ids = [];
            }

            if (!in_array($row_id, $row_ids)) {
                self::removeEntry($cache_key);

                $row_ids[] = $row_id;
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($row_ids));
            }

            $userAttributes = UserAttribute::where([
                ['company_id', $company_id],
                ['row_id', $row_id]
            ]);

            if ($userAttributes->count() > 0) {
                $userAttributes = $userAttributes->get()->toArray();

                $rows[$company_id] = [];
                foreach ($userAttributes as $attribute) {
                    foreach ($attribute as $k => $v) {
                        $rows[$attribute['company_id']][$attribute['row_id']][$k] = $v;
                    }
                }
            }

            $attributes = AttributeData::where([
                ['company_id', $company_id],
                ['row_id', $row_id],
                ['data_type', 'user']
            ]);

            if ($attributes->count() > 0) {
                $attributes = $attributes->get();

                $details[$company_id] = [];
                foreach ($attributes as $attribute) {
                    $details[$attribute->company_id][$attribute->row_id][$attribute->code] = $attribute->value;
                }
            }

            if (!empty($rows)) {
                foreach ($rows as $id => $row) {
                    foreach ($row as $row_id => $value) {
                        $secondary = !empty($details[$id][$row_id]) ? $details[$id][$row_id] : [];
                        $primary = $value;

                        $data = array_merge($secondary, $primary);

                        $cache_key = "company_" . $id . "_row_data_" . $row_id;
                        self::removeEntry($cache_key);

                        \Cache::forever($cache_key, \GuzzleHttp\json_encode($data));
                    }
                }

                return true;
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * Generate a segment's row cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     *
     * @return bool
     */
    public static function segments($segment)
    {
        $company_id = $segment->company_id;
        $segment_id = $segment->id;

        $cache_key = "company_" . $company_id . "_segments";
        $segments = \Cache::get($cache_key);
        if (isset($segments)) {
            $segments = \GuzzleHttp\json_decode($segments, true);
        } else {
            $segments = [];
        }

        if (!in_array($segment_id, $segments)) {
            $segments[] = $segment->id;

            self::removeEntry($cache_key);
            \Cache::forever($cache_key, \GuzzleHttp\json_encode($segments));

            return true;
        }

        return false;
    }

    /**
     * Generate a segment's row cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     * @param bool $sp
     *
     * @return bool
     */
    public static function segmentCache($segment, $sp = false)
    {
        try {
            $company_id = $segment->company_id;
            $segment_id = $segment->id;

            $cache_key = "company_" . $company_id . "_segment_" . $segment_id . "_rows";

            if ($sp === true) {
                $rows = \DB::select("CALL sp_get_segment_rowid({$segment->id})");
                $rows = collect($rows)->filter(function ($row) {
                    return isset($row->row_id) ? $row->row_id : null;
                });

                if ($rows->count() > 0) {
                    $segment_rowids = $rows->pluck('row_id')->unique()->toArray();

                    self::removeEntry($cache_key);
                    \Cache::forever($cache_key, \GuzzleHttp\json_encode($segment_rowids));

                    return true;
                } else {
                    self::removeEntry($cache_key);
                }
            } else {
                $attributes = UserAttribute::selectRaw("DISTINCT(row_id)")->where('company_id', $segment->company_id)
                    ->whereRaw("(" . $segment->criteria . ")");

                if ($attributes->count() > 0) {
                    $segment_rowids = $attributes->pluck('row_id')->unique()->toArray();

                    self::removeEntry($cache_key);
                    \Cache::forever($cache_key, \GuzzleHttp\json_encode($segment_rowids));

                    return true;
                } else {
                    self::removeEntry($cache_key);
                }
            }
        } catch (\Exception $exception) {

        }

        return false;
    }

    /**
     * Generate a segment's row cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     *
     * @return array
     */
    public static function getSegmentCache($segment)
    {
        $company_id = $segment->company_id;
        $segment_id = $segment->id;

        $cache_key = "company_" . $company_id . "_segment_" . $segment_id . "_rows";
        $data = \Cache::get($cache_key);

        return !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
    }

    /**
     * Create a campaign's associated segments cache data.
     *
     * @param \Illuminate\Database\Eloquent\Model $segment
     *
     * @return bool
     */
    public static function campaignSegmentsCache($segment)
    {
        $campaignSegments = [];
        $campaigns = $segment->campaigns;

        if (($campaigns instanceof \Illuminate\Support\Collection) && ($campaigns->count() > 0)) {
            $campaignIds = $campaigns->pluck('id')->toArray();
            foreach ($campaignIds as $campaignId) {
                if (!isset($campaignSegments[$campaignId])) {
                    $campaignSegments[$campaignId] = [];
                }

                if (!in_array($segment->id, $campaignSegments[$campaignId])) {
                    $campaignSegments[$campaignId][] = $segment->id;
                }
            }
        }

        if (!empty($campaignSegments)) {
            foreach ($campaignSegments as $campaign_id => $segmentIds) {
                $campaignSegmentIds = self::campaignSegments($segment->company_id, $campaign_id);
                $campaignSegmentIds = collect($campaignSegmentIds)->merge($segmentIds)
                    ->unique()->toArray();

                $cache_key = "company_" . $segment->company_id . "_campaign_" . $campaign_id . "_segments";

                self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode($campaignSegmentIds));
            }

            return true;
        }

        return false;
    }

    /**
     * Get campaign's segments cache data.
     *
     * @param int $company_id
     * @param int $campaign_id
     * @param bool $rows
     *
     * @return array
     */
    public static function campaignSegments($company_id, $campaign_id, $rows = false)
    {
        $cache_key = "company_" . $company_id . "_campaign_" . $campaign_id . "_segments";
        $data = \Cache::get($cache_key);

        if (($rows === true) && !empty($data)) {
            $rows = [];

            $segmentIds = \GuzzleHttp\json_decode($data, true);
            foreach ($segmentIds as $segmentId) {
                $cache_key = "company_" . $company_id . "_segment_" . $segmentId . "_rows";
                $data = \Cache::get($cache_key);

                $rowIds = !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
                $rows = array_merge($rows, $rowIds);
            }

            return !empty($rows) ? array_unique($rows) : [];
        } else {
            return !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
        }
    }

    /**
     * Return a company's attribute data cache.
     *
     * @param int $company_id
     *
     * @return array
     */
    public static function rows($company_id)
    {
        $cache_key = "company_" . $company_id . "_rows";
        $row_ids = \Cache::get($cache_key);
        if (!empty($row_ids)) {
            $row_ids = \GuzzleHttp\json_decode($row_ids, true);
        }
        $rows = [];
        if (!empty($row_ids)) {
            foreach ($row_ids as $row_id) {
                $key = "company_" . $company_id . "_row_data_" . $row_id;
                $row = \Cache::get($key);
                if (!empty($row)) {
                    $rows[$row_id] = \GuzzleHttp\json_decode($row, true);
                }
            }
        }
//        return $rows;
        return [1];
    }

    /**
     * Get a row's attribute data cache.
     *
     * @param int $company_id
     * @param int $row_id
     * @param bool $detail
     *
     * @return array
     */
    public static function row($company_id, $row_id, $detail = false)
    {
        $key = ($detail === true) ? 'details' : 'data';
        $cache_key = "company_" . $company_id . "_row_" . $key . "_" . $row_id;
        $data = \Cache::get($cache_key);

        return !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
    }

    /**
     * Generate user app cache data.
     *
     * @param int $company_id
     * @param int $row_id
     */
    public static function appCache($company_id, $row_id)
    {
        $row = CompanyAttributeData::row($company_id, $row_id);
        $app = [
            'row_id' => $row_id,
            'company_id' => $company_id,
            'data' => $row
        ];

        $cache_key = "company_" . $company_id . "_" . strtolower(str_replace(" ", "_", $row['app_name'])) . "_" . $row['user_id'];

        self::removeEntry($cache_key);
        \Cache::forever($cache_key, \GuzzleHttp\json_encode($app));
    }

    /**
     * Remove a user's cache.
     *
     * @param int $company_id
     * @param string $app_name
     * @param int $user_id
     *
     * @return bool
     */
    public static function removeUser($company_id, $app_name, $user_id)
    {
        try {
            $app_name = strtolower(str_replace(" ", "_", trim($app_name)));

            $cache_key = "{$company_id}_{$app_name}_{$user_id}";
            $data = \Cache::get($cache_key);
            if (!empty($data)) {
                self::removeEntry($cache_key);

                $data = \GuzzleHttp\json_decode($data, true);
                $row_id = $data['row_id'];

                self::removeEntries($company_id, $row_id);

                return true;
            }
        } catch (\Exception $exception) {
        }

        return false;
    }

    /**
     * Remove a row's cache.
     *
     * @param int $company_id
     * @param int $row_id
     *
     * @return bool
     */
    public static function removeEntries($company_id, $row_id)
    {
        try {
            $cache_key = "company_" . $company_id . "_rows";
            $row_ids = \Cache::get($cache_key);

            if (!empty($row_ids)) {
                $row_ids = \GuzzleHttp\json_decode($row_ids, true);
            } else {
                $row_ids = [];
            }

            if (in_array($row_id, $row_ids)) {
                foreach ($row_ids as $key => $row) {
                    if (in_array($row, [$row_id])) {
                        unset($row_ids[$key]);
                    }
                }

                self::removeEntry($cache_key);
                \Cache::forever($cache_key, \GuzzleHttp\json_encode(
                    array_values($row_ids)
                ));

                $cache_key = "company_" . $company_id . "_row_details_" . $row_id;
                self::removeEntry($cache_key);

                $cache_key = "company_" . $company_id . "_row_data_" . $row_id;
                self::removeEntry($cache_key);

                return true;
            }

            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Remove entry from cache.
     *
     * @param string $cache_key
     */
    public static function removeEntry($cache_key)
    {
        if (!in_array(config('cache.default'), ['array', 'database', 'file'])) {
            \Artisan::call('cache:clear', [
                '--tags' => $cache_key
            ]);
        }

        \Cache::forget($cache_key);
    }

    public static function syncCompanyCache($row_id, $company_id)
    {

        $attributeData = AttributeData::where("row_id", $row_id)->select("code", "value")->where("company_id", $company_id)->where("data_type", "user")->get();
        $formatedData = self::getFormatedData($attributeData);
        $dataTOSaveInCache = array("row_id" => $row_id, "company_id" => $company_id, "data" => $formatedData);
        $cacheKey = CommonHelper::generateCacheKey($company_id, $formatedData['user_id'], $formatedData['app_name']);
        \Cache::forget($cacheKey);
        \Cache::forever($cacheKey, json_encode($dataTOSaveInCache));
    }

    public static function getFormatedData($attributeData)
    {

        $arr = [];

        foreach ($attributeData as $attributeDatum) {

            $arr[$attributeDatum->code] = $attributeDatum->value;
        }

        return $arr;
    }

    public static function fixAttributeData($arrRows)
    {

        foreach ($arrRows as $row) {

            $attributeData = AttributeData::where("row_id", $row->row_id)
                ->select("code", "value")
                ->where("company_id", $row->company_id)
                ->where("data_type", "user")
                ->get();
            $temArr = self::getFormatedData($attributeData);
            if (isset($temArr['user_id']) and isset($temArr['app_name'])) {

                if (self::removeUser($row->company_id, $temArr['app_name'], $temArr['user_id'])) {

                    $attrb = AttributeData::where("value", $temArr['app_name'])->where("row_id", $row->row_id)->where("company_id", $row->company_id)->where("data_type", "user")->first();
                    $attrb->delete();
                    self::fixAttributeData($arrRows);
                }
            }

            AttributeData::where("row_id", $row->row_id)->delete();
        }

        return false;
    }


    public static function storeUserAttributes($companyId, $postedData, $row_id = null)
    {


        $userAttributeColumns = CompanyAttributeData::getUserAttributesColumns();
        $dataToSave = [];
        foreach ($userAttributeColumns as $column) {

            if (isset($postedData[$column])) {
                $dataToSave[$column] = $postedData[$column];
            }
        }

        $dataToSave['company_id'] = $companyId;
        if ($row_id) {

            UserAttribute::where('row_id', $row_id)
                ->update($dataToSave);
        } else {

            $row_id = UserAttribute::insertGetId($dataToSave);
        }

        $postedData = self::getExtraAttributes($postedData);
        return array($row_id, $postedData);
    }


    public static function getExtraAttributes($postedData)
    {

        $columns = self::getUserAttributesColumns();
        foreach ($columns as $column) {

            unset($postedData[$column]);
        }
        return $postedData;
    }

    public static function getUserData($companyId, $postData)
    {

        $userAttribute = UserAttribute::where("company_id", $companyId)->where("app_name", $postData['app_name'])->where("user_id", $postData['user_id'])->first();

        return $userAttribute;
    }

    public static function getUserAttributesColumns()
    {

        $sqlString = "SELECT DISTINCT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='user_attribute' ";

        $userAttributeColumns = DB::select($sqlString);

        return array_pluck($userAttributeColumns, 'COLUMN_NAME');
//        return $userAttribute;
    }
}
