<?php

namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\BoardRateLimitRules;
use Illuminate\Support\Facades\DB;

class SettingStep
{
    public function process($data, Board $board)
    {
        if (array_search(Board::STEP_SETTING, Board::STEP_LEVEL) > array_search($board->step, Board::STEP_LEVEL))
            $board->step = Board::STEP_SETTING;

        if ($data['rateLimit']['enable']) {
            $rateLimit = BoardRateLimitRules::where("board_id", $board->id)->first();
            if (!$rateLimit)
                $rateLimit = new BoardRateLimitRules();

            $rateLimit->board_id = $board->id;
            $rateLimit->rate_limit = $data['rateLimit']['boardRateLimit'];
            $rateLimit->duration_value = $data['rateLimit']['durationValue'];
            $rateLimit->duration_unit = $data['rateLimit']['durationUnit'];
            $rateLimit->save();

        } else {
            BoardRateLimitRules::where("board_id", $board->id)->delete();
        }

        /*if ($data['selectedScheduleType']['value'] != 'once') {
            if ($data['deliveryEnable']) {
                $board->delivery_control = 1;
                $board->delivery_control_delay_value = $data['deliveryControl']['value'];
                $board->delivery_control_delay_unit = $data['deliveryControl']['selectedUnit']['value'];
            } else {
                $board->delivery_control = 0;
            }
        } else {
            $board->delivery_control = 0;
        }*/
        if ($data['deliveryEnable']) {
            $board->delivery_control = 1;
            $board->delivery_control_delay_value = $data['deliveryControl']['value'];
            $board->delivery_control_delay_unit = $data['deliveryControl']['selectedUnit']['value'];
        } else {
            $board->delivery_control = 0;
        }

        $board->capping = $data['cappingValue'] ? 1 : 0;

        $board->save();

        return $board->fresh();
    }

    public function getStep($boardId)
    {
        $targetObj = (object)[];
        $targetObj->step = Board::STEP_SETTING;

        $targetObj->rateLimit = (object)[];

        $board = DB::table("board")
            ->where("id", $boardId)
            ->first();


        $targetObj->deliveryEnable = $board->delivery_control == 1 ? true : false;

        $targetObj->selectedScheduleType = (object)[];


        if ($board->schedule_type == null) {
            $targetObj->selectedScheduleType->label = "Once";
            $targetObj->selectedScheduleType->value = "once";
        } else {
            $targetObj->selectedScheduleType->label = ucfirst($board->schedule_type);
            $targetObj->selectedScheduleType->value = $board->schedule_type;
        }

        $rateLimit = BoardRateLimitRules::where("board_id", $boardId)->first();

        $targetObj->rateLimit->enable = $rateLimit ? true : false;

        if ($targetObj->rateLimit->enable) {
            $targetObj->rateLimit->boardRateLimit = $rateLimit->rate_limit;
            $targetObj->rateLimit->durationValue = $rateLimit->duration_value;
            $targetObj->rateLimit->durationUnit = $rateLimit->duration_unit;
        } else {
            $targetObj->rateLimit->boardRateLimit = 1;
            $targetObj->rateLimit->durationValue = 100;
            $targetObj->rateLimit->durationUnit = "minutes";
        }

        $targetObj->deliveryControl = (object)[];
        $targetObj->deliveryControl->value = $board->delivery_control_delay_value;
        $targetObj->deliveryControl->selectedUnit = (object)[];


        if ($board->delivery_control_delay_unit == null) {
            $targetObj->deliveryControl->selectedUnit->label = "Minute";
            $targetObj->deliveryControl->selectedUnit->value = "minute";
        } else {
            $targetObj->deliveryControl->selectedUnit->label = ucfirst($board->delivery_control_delay_unit);
            $targetObj->deliveryControl->selectedUnit->value = $board->delivery_control_delay_unit;
        }

        $targetObj->deliveryControl->selectedPriority = (object)[];
        $targetObj->deliveryControl->selectedPriority->label = ucfirst($board->priority);
        $targetObj->deliveryControl->selectedPriority->value = $board->priority;
        $targetObj->cappingValue = $board->capping == 1 ? true : false;

        return $targetObj;

    }

}