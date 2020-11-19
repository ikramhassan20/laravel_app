<?php

namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\Components\RandomString;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BasicStep
{

    public function process($data, Board $board)
    {
        $board->step = Board::STEP_BASIC;

        $user = request()->user();
        $boardId = [];
        if (isset($board->id)) {
            $boardId[] = $board->id;
        }

        $duplicateRecord = Board::where("app_group_id", $user->currentAppGroup()->id)
            ->where("name", $data['name'])
            ->whereNotIn("id", $boardId)
            ->first();

        if ($duplicateRecord) {
            return [
                "dialogueOpen" => "true",
                "status" => false,
                "message" => "Board Name Already Exist"
            ];
        }

        $app_group = $user->currentAppGroup();
        $board->code = RandomString::generateWithPrefix('board');
        $board->tags = implode(",", $data['tagsOriginal']);
        $board->name = $data['name'];
        $board->app_group_id = $app_group->id;
        $board->start_time = Carbon::now();
        $board->end_time = Carbon::now();
        $board->created_by = $user->id;
        $board->updated_by = $user->id;
        $board->save();


        return $board->refresh();
    }

    public function getStep($boardId)
    {
        $generalStep = DB::table("board")
            ->where('id', $boardId)
            ->select("id as boardId","name", "code", "tags as tagsOriginal", "status", "step as currentStep", "updated_at")
            ->first();

        //dd($generalStep);
        $generalStep->tagsOriginal = $generalStep->tagsOriginal == "" ? [] : explode(",", $generalStep->tagsOriginal);

        $generalStep->step = Board::STEP_BASIC;

        return $generalStep;
    }
}