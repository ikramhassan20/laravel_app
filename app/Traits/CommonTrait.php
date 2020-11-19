<?php

namespace App\Traits;

use App\AttributeData;
use App\CampaignQueue;
use App\Components\CampaignDispatcher;
use App\Components\BoardDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\BoardQueue;

trait CommonTrait
{
    public function campaignQueueDispatch($queue)
    {
        $campaign_id = $queue->campaign_id;

        //$delay = $startTime->diffInSeconds($queue->start_at);
        Log::info("Campaign started dispatching.");

        try{
            // trigger campaign dispatcher
            // generate payload and maintain cache
            $dispatcher = new CampaignDispatcher($campaign_id);
            $dispatcher->processCampaignDispatcherMakeJob();

            // updating campaign queue status to completed.
            $queue->status = CampaignQueue::STATUS_COMPLETE;
            $queue->error_message = "";
            $queue->save();
        }
        catch (\Exception $exception){
            // updating campaign queue status to completed.
            $queue->error_message = $exception->getMessage();
            $queue->status = CampaignQueue::STATUS_FAILED;
            $queue->save();
        }
    }

    public function boardQueueDispatch($queue)
    {
        $board_id = $queue->board_id;

        //$delay = $startTime->diffInSeconds($queue->start_at);
        Log::info("Board started dispatching.");

        try{
            // trigger campaign dispatcher
            // generate payload and maintain cache
            $dispatcher = new BoardDispatcher($board_id);
            $dispatcher->processBoardDispatcherMakeJob();

            // updating campaign queue status to completed.
            $queue->status = BoardQueue::STATUS_COMPLETE;
            $queue->error_message = "";
            $queue->save();
        }
        catch (\Exception $exception){
            Log::error($exception->getMessage());
            // updating campaign queue status to completed.
            $queue->error_message = $exception->getMessage();
            $queue->status = CampaignQueue::STATUS_FAILED;
            $queue->save();
        }
    }

    public function getFileSize($size)
    {
        $type = "KB";

        if ($size >= 1024) {
            $size = $size / 1024;
            $type = "MB";
        }

        return number_format((float)$size, 2, '.', '') . " $type";
    }

    public function generateRowId($companyId)
    {
        $microTime = time();
        $row_id = $microTime + rand(200, 99) + rand(100, 999);
        usleep(rand(0, 100));
        if (self::checkRowId($row_id, $companyId)) {
            self::generateRowId($companyId);
        }
        return (int)$row_id;
    }

    public function checkRowId($row_id, $companyId)
    {
        $attributeData = AttributeData::where("row_id", $row_id)->where("company_id", $companyId)->count();
        if ($attributeData) {
            return true;
        }
        return false;
    }

    private function saveFileToLocal($importData, $company)
    {
        $s3_file = Storage::disk('s3')->get('app/assets/' . $importData->file_name);
        $s3 = Storage::disk('local');
        $s3->put('public/company_' . $company->id . '/' . $importData->file_name, $s3_file);
    }

    public function getAllDateTimeBetweenTwoDates($start, $end, $day = null)
    {
        $period = new \DatePeriod(
            new \DateTime($start),
            new \DateInterval('P1D'),
            new \DateTime($end)
        );
        $dates = [];

        foreach ($period as $key => $value) {
            if (isset($day)) {
                if (strtolower($value->format('l')) == $day) {
                    array_push($dates, $value->format('Y-m-d H:i:s'));
                }

                continue;
            }

            array_push($dates, $value->format('Y-m-d H:i:s'));
        }

        return $dates;
    }
}