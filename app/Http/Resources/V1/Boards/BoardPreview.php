<?php


namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use App\Traits\CommonTrait;
use App\BoardQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Log;
use App\Helpers\CommonHelper as Helper;

class BoardPreview
{
    use CommonTrait, ParseResponse;

    public function process($data, Board $board)
    {
        try {
            $appGroupID = Auth::user()->currentAppGroup()->id;
            if ($board->app_group_id != $appGroupID) {
                throw new \Exception('Invalid user.');
            }
            $board->step = Board::STEP_BUILD;
            $board->status = $data['status'];
            $board->save();
            Helper::saveBoardSegmentsUnion($board->id, $board->app_group_id);
            $this->processBoardQueues($board->fresh());
            return $board->fresh();
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function processBoardQueues($board)
    {
        if ($board->delivery_type !== "schedule") {
            return false;
        }
        $dates = array();
        $start = $board->start_time;
        $end = $board->end_time;
        /*if ($board->schedule_type == "once") {
            $board->end_time = NULL;
            $board->save();
            $board->fresh();
            $this->createBoardQueues($board);
        }*/
        if ($board->schedule_type == "daily" || $board->schedule_type == "once") {
            $dates = self::getAllDateTimeBetweenTwoDates($start, $end);
        }
        if ($board->schedule_type == "weekly") {
            $schedules = $board->schedules()->select('day')->get();
            foreach ($schedules as $schedule) {
                $dates = array_merge($dates, self::getAllDateTimeBetweenTwoDates($start, $end, $schedule->day));
            }
        }
        $_queue = BoardQueue::where('board_id', '=', $board->id)->first();
        if (empty($_queue) AND count($dates) > 0) {
            foreach ($dates as $date) {
                $this->createBoardQueues($board, $date);
            }
        }
    }

    private function createBoardQueues($board, $startDateTime = null)
    {
        $startDateTime = isset($startDateTime) ? $startDateTime : $board->start_time;
        $sendingDate = Carbon::parse($startDateTime);

        $details = [
            "boardId" => $board->id,
            "scheduleType" => $board->schedule_type,
            "sendingDate" => $sendingDate->format('Y-m-d'),
            "boardDay" => ""
        ];
        $Board = Board::where('id', '=', $board->id)->where('status', '!=', 'suspended')->first();
        if ($Board) {
            $queue = new BoardQueue();
            $queue->board_id = $board->id;
            $queue->status = BoardQueue::STATUS_AVAILABLE;
            $queue->priority = BoardQueue::priority(isset($result->board_priority) ? $board->board_priority : 'medium');
            $queue->start_at = $startDateTime;
            $queue->details = json_encode($details);
            $queue->save();
        }
    }
}