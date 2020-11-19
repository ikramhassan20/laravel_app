<?php

namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\BoardSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryStep
{
    public function process($data, Board $board)
    {

        if (array_search(Board::STEP_DELIVERY, Board::STEP_LEVEL) > array_search($board->step, Board::STEP_LEVEL))
            $board->step = Board::STEP_DELIVERY;

        if ($data['subStep'] == Board::DELIVERY_TYPE_SCHEDULE) {
            if ($data['selectedScheduleType']['value'] == Board::SCHEDULE_WEEKLY) {
                BoardSchedule::where("board_id", $board->id)->delete();
                foreach ($data['selectedDays'] as $day) {
                    $boardScheduleDays = new BoardSchedule();
                    $boardScheduleDays->board_id = $board->id;
                    $boardScheduleDays->day = $day['value'];
                    $boardScheduleDays->save();
                }
            }

        }

        $board->delivery_type = $data['subStep'];

        $board->schedule_type = $data['selectedScheduleType']['value'];
        $board->start_time = $data['dateTime']['start']['date'] . ' ' . $data['dateTime']['start']['hours'] . ':' . $data['dateTime']['start']['mints'] . ':10';
        if ($data['dateTime']['end']['date'] != null)
            $board->end_time = $data['dateTime']['end']['date'] . ' ' . $data['dateTime']['end']['hours'] . ':' . $data['dateTime']['end']['mints'] . ':30';

        //        if ($data['deliveryEnable']) {
        //            $board->delivery_control = 1;
        //            $board->delivery_control_delay_value = $data['deliveryControl']['value'];
        //            $board->delivery_control_delay_unit = $data['deliveryControl']['selectedUnit']['value'];
        //        } else {
        //            $board->delivery_control = 0;
        //        }

        $board->priority = $data['deliveryControl']['selectedPriority']['value'];
//        $board->capping = $data['cappingValue'] ? 1 : 0;

        $board->save();

        return $board->fresh();
    }

    public function getStep($boardId)
    {
        $deliveryObj = (object)[];
        $board = DB::table("board")
            ->where("id", $boardId)
            ->first();

        $deliveryObj->step = Board::STEP_DELIVERY;
        $deliveryObj->subStep = $board->delivery_type;
//        $deliveryObj->deliveryEnable = $board->delivery_control == 1 ? true : false;
        $deliveryObj->selectedScheduleType = (object)[];


        if ($board->schedule_type == null) {
            $deliveryObj->selectedScheduleType->label = "Once";
            $deliveryObj->selectedScheduleType->value = "once";
        } else {
            $deliveryObj->selectedScheduleType->label = ucfirst($board->schedule_type);
            $deliveryObj->selectedScheduleType->value = $board->schedule_type;
        }

        $deliveryObj->selectedDays = [];
        if ($board->schedule_type == "weekly") {

            $days = DB::table("board_schedule")
                ->where("board_id", $boardId)
                ->select("day")->get();

            foreach ($days as $day) {
                $dayObj = (object)[];
                $dayObj->label = ucfirst($day->day);
                $dayObj->value = $day->day;
                $deliveryObj->selectedDays[] = $dayObj;
            }
        }

        $deliveryObj->dateTime = (object)[];

        $deliveryObj->dateTime->start = (object)[];

        if ($board->start_time == null) {
            $deliveryObj->dateTime->start->date = "";
            $deliveryObj->dateTime->start->hours = 0;
            $deliveryObj->dateTime->start->mints = 0;
        } else {
            $deliveryObj->dateTime->start->date = explode(" ", $board->start_time)[0];
            $deliveryObj->dateTime->start->hours = sprintf("%02d", explode(":", explode(" ", $board->start_time)[1])[0]);
            $deliveryObj->dateTime->start->mints = sprintf("%02d", explode(":", explode(" ", $board->start_time)[1])[1]);
        }


        $deliveryObj->dateTime->end = (object)[];

        if ($board->end_time == null) {
            $deliveryObj->dateTime->end->date = "";
            $deliveryObj->dateTime->end->hours = 0;
            $deliveryObj->dateTime->end->mints = 0;
        } else {
            $deliveryObj->dateTime->end->date = explode(" ", $board->end_time)[0];
            $deliveryObj->dateTime->end->hours = sprintf("%02d", explode(":", explode(" ", $board->end_time)[1])[0]);
            $deliveryObj->dateTime->end->mints = sprintf("%02d", explode(":", explode(" ", $board->end_time)[1])[1]);
        }

        $deliveryObj->deliveryControl = (object)[];
//        $deliveryObj->deliveryControl->value = $board->delivery_control_delay_value;
//        $deliveryObj->deliveryControl->selectedUnit = (object)[];
//
//
//        if ($board->delivery_control_delay_unit == null) {
//            $deliveryObj->deliveryControl->selectedUnit->label = "Minute";
//            $deliveryObj->deliveryControl->selectedUnit->value = "minute";
//        } else {
//            $deliveryObj->deliveryControl->selectedUnit->label = ucfirst($board->delivery_control_delay_unit);
//            $deliveryObj->deliveryControl->selectedUnit->value = $board->delivery_control_delay_unit;
//        }

        $deliveryObj->deliveryControl->selectedPriority = (object)[];
        $deliveryObj->deliveryControl->selectedPriority->label = ucfirst($board->priority);
        $deliveryObj->deliveryControl->selectedPriority->value = $board->priority;

        return $deliveryObj;
    }


}