<?php

namespace App\Components;

use App\BoardVariantStep;
use App\Cache\BoardUserTrackingCache;
use App\Board;
use Carbon\Carbon;
use Log;

/**
 * Class BoardDeliveryControl
 * @package App\Components
 */
class BoardDeliveryControl
{

    public static function applyDeliveryControl($board_id, $row_id)
    {
        try {
            // getting board information
            $board = Board::find($board_id);
            if(!isset($board)){
                Log::error('In Delivery Control, Board not found.');
                return false;
            }

            // getting campaign tracking from cache
            $boardUserTrackingCache = new BoardUserTrackingCache();
            $boardUserTracking = $boardUserTrackingCache->getBoardUserTrackingCache($board_id, $row_id);

            $last_sent_date = (isset($boardUserTracking->last_sent_date)) ? $boardUserTracking->last_sent_date : '';
            if($last_sent_date != ""){
                $intervalDate = '';
                $date_from_last_sent = '';
                $tmp1 = explode(' ', $last_sent_date);
                $tmp2 = explode(' ', $board->start_time);
                $date_from_last_sent = $tmp1[0]. " ".$tmp2[1];

                switch (strtolower($board->delivery_control_delay_unit)) {
                    case 'minute':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addMinutes($board->delivery_control_delay_value);
                        break;
                    case 'day':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addDays($board->delivery_control_delay_value);
                        break;
                    case 'week':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addWeeks($board->delivery_control_delay_value);
                        break;
                    case 'month':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addMonths($board->delivery_control_delay_value);
                        break;
                }

                if (!empty($intervalDate)) {
                    $now = Carbon::now(config('app.timezone'));
                    if ($now->lt($intervalDate)) {
                        return false;
                    }
                    else{
                        return true;
                    }
                }
            }

            return false;
        }
        catch (\Exception $exception) {
            Log::info($exception->getMessage());
            return false;
        }
    }


    public static function applyVariantStepDeliveryControl($board_id, $row_id, $variantStepId)
    {
        try{
            // getting board information
            //$board = Board::find($board_id);
            $board = Board::where('id', '=', $board_id)->select('id', 'start_time')->first();
            if(!isset($board)){
                Log::error('In Variant Step Delivery Control, Board not found.');
                return false;
            }

            // get variant step info
            $variantStep = BoardVariantStep::where('id', '=', $variantStepId)
                ->select('step_control_delay_unit', 'step_control_delay_value')
                ->first();

            if(!$variantStep){
                Log::error('In Variant Step Delivery Control, Variant Step not found.');
                return false;
            }

            $stepDelayValue = $variantStep->step_control_delay_value;
            $stepDelayUnit  = $variantStep->step_control_delay_unit;

            // get board user tracking from cache
            $boardUserTrackingCache = new BoardUserTrackingCache();
            $boardUserTracking = $boardUserTrackingCache->getBoardUserTrackingCache($board_id, $row_id);

            $last_sent_date = (isset($boardUserTracking->last_sent_date)) ? $boardUserTracking->last_sent_date : '';
            if($last_sent_date != ""){
                $intervalDate = '';
                $date_from_last_sent = '';
                $tmp1 = explode(' ', $last_sent_date);
                $tmp2 = explode(' ', $board->start_time);
                $date_from_last_sent = $tmp1[0]. " ".$tmp2[1];

                switch (strtolower($stepDelayUnit)) {
                    case 'minute':
                        $intervalDate = Carbon::parse($last_sent_date)
                            ->addMinutes($stepDelayValue);
                        break;
                    case 'day':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addDays($stepDelayValue)
                            ->subMinutes(15);
                        break;
                    case 'week':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addWeeks($stepDelayValue)
                            ->subHours(1);
                        break;
                    case 'month':
                        $intervalDate = Carbon::parse($date_from_last_sent)
                            ->addMonths($stepDelayValue)
                            ->subDays(1);
                        break;
                }

                if (!empty($intervalDate)) {
                    $now = Carbon::now(config('app.timezone'));
                    if ($now->lt($intervalDate)) {
                        return false;
                    }
                    else{
                        return true;
                    }
                }
            }

            return false;

        }
        catch(\Exception $e){
            Log::info($e->getMessage());
            return false;
        }

    }

}