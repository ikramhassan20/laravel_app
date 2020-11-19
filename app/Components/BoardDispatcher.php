<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\AppUsers;
use App\AppUserTokens;
use App\BoardVariant;
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
use App\Cache\BoardSegmentCache;
use App\Board;
use Illuminate\Support\Facades\Redis;
use App\Cache\CacheKeys;
use App\Cache\OnceBoardRowIdsCache;


/**
 * Class BoardDispatcher
 * @package App\Components
 */
class BoardDispatcher
{
    public $board_id;

    /**
     * assign campaign id
     *
     * @param int $campaign_id
     *
     */
    public function __construct($board_id)
    {
        $this->board_id = $board_id;
    }

    /**
     * generates payload of required data
     *
     * @return @array/bool
     */
    public function processBoardDispatcherMakeJob()
    {
        Log::info("Board dispatcher started.");

        // getting campaign general info
        $_board_general = Board::where('id', '=', $this->board_id)
            ->select('id', 'status', 'app_group_id', 'schedule_type')
            ->first();

        $board_status = (isset($_board_general->status)) ? $_board_general->status : 'draft';
        $app_group_id = (isset($_board_general->app_group_id)) ? $_board_general->app_group_id : "";

        if ($board_status != 'active') {
            $error = 'Board is not active.';
            throw new \Exception($error);
            Log::error("Board is not active.");
            return false;
        }

        // get all segments from campaign segment cache

        $segments = [];
        $segmentRows = [];
        $_boardCache = new BoardSegmentCache();
        //$_boardCache->saveBoardSegmentCache($_board_general); for testing purpose, need to remove this line
        $segments = $_boardCache->getBoardSegmentsCache($this->board_id);
        if (!isset($segments)) {
            $error = 'No board segment found.';
            throw new \Exception($error);
            Log::error($error);
            return false;
        }

        /*echo "segments \n";
        print_r($segments);*/

        // old code reference
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
        }*/

        // new code paging/scan changes
        $objCachekey = new CacheKeys($app_group_id);
        $cache_prefix =  config('cache.prefix');
        $unionStoreKey = $cache_prefix . ":" . $objCachekey->generateBoardSegmentsUnionCacheKey($this->board_id);
        $unionCacheKeys = \App\Helpers\CommonHelper::getSegmentsUnionCacheKeys($segments, $app_group_id);

        if (sizeof($segments) > 0) {

            if($_board_general->schedule_type ==  Board::SCHEDULE_ONCE){
                $objOnceBoardCache = new OnceBoardRowIdsCache();
                $onceBoardCache = $objOnceBoardCache->getOnceBoardRowIdsCache($app_group_id, $this->board_id);
                if($onceBoardCache){
                    $cache_refresh = false;
                }
                else{
                    $cache_refresh = true;
                }
            }
            else{
                $cache_refresh = true;
            }
            //var_dump($cache_refresh);

            if($cache_refresh === true){

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

        }

        $isKey = (bool)Redis::exists($unionStoreKey);
        if($isKey === false ){
            $error = "Board Segments Union Cache not exist.";
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
                $board_segment_rows = $readData['rowIds'];
                $offset = $readData['offset'];
                //print_r($readData);

                if ($board_segment_rows !== null && isset($board_segment_rows) && sizeof($board_segment_rows) > 0) {
                    try{
                        // started dispatch process
                        $response = BoardDispatchProcess::processDispatcher($board_segment_rows, $this->board_id);
                        $response = \GuzzleHttp\json_decode($response, true);

                        if(isset($response) && $response['status'] == AppStatusCodes::HTTP_OK){
                            // now parse object and get separate all payloads from above array
                            $payload_params = $response['data'];
                        }
                        else{
                            Log::info($response['data']);
                            $error = (isset( $response['data'][0])) ? $response['data'][0] : "Unable to generate payload for board. ";
                            throw new \Exception($error);
                            return false;
                        }
                        // clean response from memory
                        unset($response);
                    }
                    catch (\Exception $exception){
                        $error = "Unable to generate payload for board. ";
                        throw new \Exception($exception->getMessage());
                        Log::error($error.$exception->getMessage());
                        return false;
                    }
                }

                if($readData['paging'] === false || $readData['offset'] == 0){
                    $condition = false;
                }

            }

            return true;
        }


        /*if (!isset($row_ids)) {
            $error = 'No segment user(s) found.';
            throw new \Exception($error);
            Log::error($error);
            return false;
        }*/

        /*$board_segment_rows = [];
        if (isset($row_ids)) {
            $board_segment_rows = array_unique($row_ids);
        }*/

        // unset $row_ids to free memory space
        //unset($row_ids);

        /*echo "rowIds \n";
        print_r($board_segment_rows);*/

        // when board segments have unique users
        //if ($board_segment_rows !== null && isset($board_segment_rows) && sizeof($board_segment_rows) > 0) {

            //Log::info("User rows: " . \GuzzleHttp\json_encode($campaign_segment_rows));

            /*try{
                // started dispatch process
                $response = BoardDispatchProcess::processDispatcher($board_segment_rows, $this->board_id);
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
                $error = "Unable to generate payload for board. ";
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
                        $priority = (isset($_payload['payload']['data']['priority'])) ? $_payload['payload']['data']['priority'] : Board::PRIORITY_MEDIUM;
                        $variant_name = "";
                        $variant_code = (isset($_payload['variant_code'])) ? $_payload['variant_code'] : 'variant1';
                        if($variant_code != "variant1"){
                            $variant_name = $variant_code;
                        }

                        //$delay = $startTime->diffInSeconds($_payload['payload_interval']);

                        if (strtolower($_payload['payload']['data']['type']) == strtolower(BoardVariant::VARIANT_INAPP_CODE)  || strtolower($_payload['payload']['data']['type']) == strtolower(BoardVariant::VARIANT_PUSH_CODE)) {
                            // adding PushJobWorker Queue
                            $queueName = strtolower($_payload['payload']['data']['type']).$priority.$variant_name;
                            PushJobWorker::dispatch($_payload['payload'])
                                ->onQueue("$queueName")
                                ->delay(Carbon::now());
                        } else {
                            // adding EmailJobWorker Queue
                            $queueName = strtolower($_payload['payload']['data']['type']).$priority.$variant_name;
                            EmailJobWorker::dispatch($_payload['payload'])
                                ->onQueue("$queueName")
                                ->delay(Carbon::now());
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
            }*/

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
