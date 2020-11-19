<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\AppUsers;
use App\AppUserTokens;
use App\Campaign;
use App\CampaignSegment;
use App\Queue;
use App\Translation;
use App\User;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Support\Facades\App;
use App\Cache\CampaignSegmentCache;
use App\Cache\AppGroupSegmentCache;
use App\Jobs\PushJobWorker;
use App\Jobs\EmailJobWorker;
use App\Components\CampaignDispatchProcess;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Cache\CacheKeys;

/**
 * Class CampaignDispatcher
 * @package App\Components
 * @todo build and generate the campaign dispatcher payload
 */
class CampaignDispatcher
{
    public $campaign_id;

    /**
     * assign campaign id
     *
     * @param int $campaign_id
     *
     */
    public function __construct($campaign_id)
    {
        $this->campaign_id = $campaign_id;
    }

    /**
     * generates payload of required data
     *
     * @return @array/bool
     */
    public function processCampaignDispatcherMakeJob()
    {
        Log::info("Campaign dispatcher started.");

        // getting campaign general info
        $_campaign_general = Campaign::find($this->campaign_id);
        $campaign_status = (isset($_campaign_general->status)) ? $_campaign_general->status : 'draft';
        $campaign_type = (isset($_campaign_general->campaign_type)) ? $_campaign_general->campaign_type : "";
        $app_group_id = (isset($_campaign_general->app_group_id)) ? $_campaign_general->app_group_id : "";

        if ($campaign_status != 'active') {
            $error = 'Campaign is not active.';
            throw new \Exception($error);
            Log::error("Campaign is not active.");
            return false;
        }

        // get all segments from campaign segment cache
        $segments = [];
        $segmentRows = [];
        $_campaign = new CampaignSegmentCache();
        $segments = $_campaign->getCampaignSegmentsCache($this->campaign_id);
        if (!isset($segments)) {
            $error = 'No Campaign segment found.';
            throw new \Exception($error);
            Log::error($error);
            return false;
        }

        // new code paging/scan changes
        $objCachekey = new CacheKeys($app_group_id);
        $cache_prefix =  config('cache.prefix');
        $unionStoreKey = $cache_prefix . ":" . $objCachekey->generateCampaignSegmentsUnionCacheKey($this->campaign_id);
        $unionCacheKeys = \App\Helpers\CommonHelper::getSegmentsUnionCacheKeys($segments, $app_group_id);

        if (sizeof($segments) > 0) {

            try{
                Redis::sunionstore($unionStoreKey, $unionCacheKeys);
            }
            catch(\Exception $e){
                $error = $e->getMessage();
                throw new \Exception($error);
                Log::error($error);
                return false;
            }

        }

        $isKey = (bool)Redis::exists($unionStoreKey);
        if($isKey === false ){
            $error = "Campaign Segments Union Cache not exist.";
            throw new \Exception($error);
            Log::error($error);
            return false;
        }

        if($isKey === true ){

            $condition = true;
            $offset = 0;
            $limit = (!empty(config('engagement.api.limit.redis_cache_limit')) ? config('engagement.api.limit.redis_cache_limit') : 50000);
            while($condition){
                $readData = \App\Helpers\CommonHelper::readDataFromRedisUnionCache($unionStoreKey, $limit, $offset);
                $campaign_segment_rows = $readData['rowIds'];
                $offset = $readData['offset'];
                //print_r($readData);

                if ($campaign_segment_rows !== null && isset($campaign_segment_rows) && sizeof($campaign_segment_rows) > 0) {
                    try {
                        // started dispatch process
                        $response = CampaignDispatchProcess::processDispatcher($campaign_segment_rows, $this->campaign_id);
                        $response = \GuzzleHttp\json_decode($response, true);

                        if (isset($response) && $response['status'] == AppStatusCodes::HTTP_OK) {
                            // now parse object and get separate all payloads from above array
                            $payload_params = $response['data'];
                        } else {
                            Log::info($response['data']);
                            $error = (isset($response['data'][0])) ? $response['data'][0] : "Unable to generate payload for campaign. ";
                            throw new \Exception($error);
                            return false;
                        }
                        // clean response from memory
                        unset($response);
                    }
                    catch (\Exception $exception) {
                        $error = "Unable to generate payload for campaign. ";
                        throw new \Exception($exception->getMessage());
                        Log::error($error . $exception->getMessage());
                        return false;
                    }
                }

                if($readData['paging'] === false || $readData['offset'] == 0){
                    $condition = false;
                }

            }

            return true;
        }


        /**
         * Old code Reference
         */


        /*if (sizeof($segments) > 0) {
            $segmentRows = new AppGroupSegmentCache();

            foreach ($segments as $_segment) {
                $_segment_rows = $segmentRows->getAppGroupSegmentRowsCache($app_group_id, $_segment);

                if ($_segment_rows !== null && (isset($_segment_rows)) && sizeof($_segment_rows) > 0) {
                    foreach ($_segment_rows as $_row_id) {
                        $row_ids[] = $_row_id;
                    }
                }
            }
        }
        if (!isset($row_ids)) {
            $error = 'No segment user(s) found.';
            throw new \Exception($error);
            Log::error($error);
            return false;
        }

        $campaign_segment_rows = [];
        if (isset($row_ids)) {
            $campaign_segment_rows = array_unique($row_ids);
        }*/

        // when campaign segments have unique users
        /*if ($campaign_segment_rows !== null && isset($campaign_segment_rows) && sizeof($campaign_segment_rows) > 0) {

            //Log::info("User rows: " . \GuzzleHttp\json_encode($campaign_segment_rows));

            try{
                // started dispatch process
                $response = CampaignDispatchProcess::processDispatcher($campaign_segment_rows, $this->campaign_id);
                $response = \GuzzleHttp\json_decode($response, true);

                if(isset($response) && $response['status'] == AppStatusCodes::HTTP_OK){
                    // now parse object and get separate all payloads from above array
                    $payload_params = $response['data'];
                }
                else{
                    Log::info($response['data']);
                    $error = (isset( $response['data'][0])) ? $response['data'][0] : "Unable to generate payload for campaign. ";
                    throw new \Exception($error);
                    return false;
                }
                // clean response from memory
                unset($response);
            }
            catch (\Exception $exception){
                // exception generated
                $error = "Unable to generate payload for campaign. ";
                throw new \Exception($exception->getMessage());
                Log::error($error.$exception->getMessage());
                return false;
            }*/

             /*try {

                $startTime = Carbon::now()->startOfMinute();

                if (isset($payload_params) && sizeof($payload_params) > 0) {

                    // loop through all payloads
                    foreach ($payload_params as $_payload) {
                        //Log::info("Payload: " . \GuzzleHttp\json_encode($_payload));
                        $priority = (isset($_payload['payload']['data']['priority'])) ? $_payload['payload']['data']['priority'] : Campaign::PRIORITY_MEDIUM;
                        $variant_name = "";
                        $variant_code = (isset($_payload['variant_code'])) ? $_payload['variant_code'] : 'variant1';
                        if($variant_code != "variant1"){
                            $variant_name = $variant_code;
                        }

                        //$delay = $startTime->diffInSeconds($_payload['payload_interval']);

                        if ($campaign_type == Campaign::CAMPAIGN_INAPP_CODE || $campaign_type == Campaign::CAMPAIGN_PUSH_CODE) {
                            // adding PushJobWorker Queue
                            PushJobWorker::dispatch($_payload['payload'])
                                ->onQueue("$campaign_type$priority$variant_name");
                                //->delay(Carbon::now());
                        } else {
                            // adding EmailJobWorker Queue
                            EmailJobWorker::dispatch($_payload['payload'])
                                ->onQueue("$campaign_type$priority$variant_name");
                                //->delay(Carbon::now());
                        }
                        //Log::info("Added to queue.");
                    }
                }
                // clean payload from memory
                unset($payload_params);

            } catch (\Exception $exception) {
                // exception generated
                $error = "Unable to create campaign job. " . $exception->getMessage();
                Log::error($error);
                //throw new \Exception($error);
                return false;
            } */
            // return output as true for successful execution
            //return true;
        //}
        return false;
    }

    /**
     * get template contents on the basis of variants
     *
     * @param int $campaign_id
     * @param int $variant_id
     * @param string $language
     *
     * @return array
     */
    public function getTemplateContent($campaign_id, $variant_id, $language)
    {
        $template_content = "";
        $_translations = Translation::where(['translatable_id' => $variant_id, 'translatable_type' => 'campaign'])->get();

        foreach ($_translations as $_translation) {
            $template = $_translation->template;
            $_template = json_decode($template);

            if (isset($_template)) {
                if ($_template->language == $language) {
                    $template_content = $_template->templateInfo->template;
                }
            }
        }
        return $template_content;
    }

}
