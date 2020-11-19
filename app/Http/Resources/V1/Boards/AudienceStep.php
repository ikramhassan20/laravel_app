<?php

namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\Cache\BoardSegmentCache;
use Illuminate\Support\Facades\DB;

class AudienceStep
{
    public function process($data, Board $board)
    {
        if (array_search(Board::STEP_AUDIENCE, Board::STEP_LEVEL) > array_search($board->step, Board::STEP_LEVEL))
            $board->step = Board::STEP_AUDIENCE;

        $board->segments()->sync($data['targetObj']['segments']);
        $board->save();

        $board_segment_cache = new BoardSegmentCache();
        $board_segment_cache->saveBoardSegmentCache($board);

        return $board->fresh();

    }

    public function getStep($boardId)
    {
        $targetObj = (object)[];
        $targetObj->totalSelectedSegments = DB::table("board_segment")
            ->join("segment", "board_segment.segment_id", "=", "segment.id")
            ->where("board_segment.board_id", $boardId)
            ->select("segment.name as label", "segment.id as value")
            ->get();

        return $targetObj;

    }

}