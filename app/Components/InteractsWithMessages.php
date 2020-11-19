<?php

namespace App\Components;

use App\Campaign;
use App\CampaignTracking;
use App\CampaignTrackingLog;
use App\Helpers\CommonHelper;
use App\Http\Resources\V1\Notifications\SendNotifications;
use App\Notification;
use App\NotificationsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait InteractsWithMessages
{
    public function saveNotification($data, $response)
    {
        $notification = new Notification();
        $notificationLogs = new NotificationsLog();
        $notification->email = $data['to'];
        $notification->payload = \GuzzleHttp\json_encode(
            array_filter($data),
            true
        );
        $notification->save();
        if ($response['status'] == 'success') {
            $notification->sent = true;
            $notification->sent_at = Carbon::now();
            $notification->save();
            CommonHelper::saveNotificationlogs($notification->id, 'success', 'Notification Send Successfully');
        } else {
            $notification->sent = false;
            $notification->sent_at = Carbon::now();
            $notification->save();
            CommonHelper::saveNotificationlogs($notification->id, 'success', 'Notification Send Successfully');
        }
        return $notification;
    }

    /**
     * @param $campaign Campaign
     * @return float|int|mixed
     */
    public function getInterval($campaign)
    {
        $interval = config('engagement.queue.interval');

        if ($campaign->isDeliveryTypeAction() && !empty($campaign->action_trigger_delay_value) && !empty($campaign->action_trigger_delay_unit)) {
            switch ($campaign->action_trigger_delay_unit) {
                case CommonHelper::$_SECOND_API_TRIGGER:
                    $interval = $campaign->action_trigger_delay_value;
                    break;
                case CommonHelper::$_MINUTE_API_TRIGGER:
                    $interval = $campaign->action_trigger_delay_value * 60;
                    break;
                case CommonHelper::$_HOUR_API_TRIGGER:
                    $interval = $campaign->action_trigger_delay_value * 3600;
                    break;
            }
        }

        return $interval;
    }

    /**
     * Send email to users.
     *
     * @param \Illuminate\Database\Eloquent\Model $campaign
     * @param array $data
     *
     * @throws Exception
     *
     * @return array|string
     */
    protected function sendEmail($payload)
    {
        $response = array();
        try {
            $data = array(
                'from_email' => $payload['email_from'], //$payload['email_from']
                'from_name' => $payload['email_from_name'],
                'to' => $payload['to_email'],
                'subject' => $payload['email_subject'],
                'template_content' => $payload['email_body'],
            );

            //Log::info('Email worker payload: ' . \GuzzleHttp\json_encode($data));

            Mail::send(['html' => 'emails.default'], $data,
                function ($message) use ($data) {
                    $message->to($data['to']);
                    $message->subject($data['subject']);
                    $message->from($data['from_email'], $data['from_name']);
                });

            $response = array(
                'status' => 'success',
                'data' => [],
                'message' => 'Email sent successfully.'
            );
            //Log::info('Response sending: ' . \GuzzleHttp\json_encode($response));
        } catch (\Exception $exception) {
            $response = array(
                'status' => 'error',
                'data' => [],
                'message' => $exception->getMessage()
            );
        }
        return $response;
    }

    /**
     * saving tracking logs of failed email,
     */
    protected function trackingLogsFailedEmail(\Exception $exception, $payload)
    {
        $this->updateFailedTrackingStatus($payload['track_key'], Campaign::CAMPAIGN_TRACKING_FAILED_STATUS);
        // Processing to be done when handling failed
        $response = [
            'campaign_tracking_id' => $payload['campaign_tracking_id'],
            'status' => 'error',
            'message' => $exception->getMessage()
        ];
        $result = CampaignTrackingLog::create($response);
        return $result;
    }

    /**
     * updating the campaign tracking status failed,
     */
    protected function updateFailedTrackingStatus($trackingKey, $status)
    {
        $campaignTrackingResponse = CampaignTracking::where('track_key', $trackingKey)->update([
            'status' => $status,
            'ended_at' => Carbon::now()
        ]);
        return $campaignTrackingResponse;
    }

    /**
     * saving tracking logs of failed push / inapp,
     */
    protected function trackingLogsFailedPushInApp(\Exception $exception, $payload)
    {
        try {
            // Processing to be done when handling failed
            $response = [
                'campaign_tracking_id' => $payload['campaign_tracking_id'],
                'status' => 'error',
                'message' => $exception->getMessage()
            ];
            $result = CampaignTrackingLog::create($response);
        } catch (\Exception $exception) {
            $result = $exception->getMessage();
        }
        return $result;
    }

    /**
     * saving tracking logs of failed push / inapp,
     */

    protected function failedTrackingLOgs($payload)
    {
        try {
            // Processing to be done when handling failed
            $response = [
                'campaign_tracking_id' => $payload['campaign_tracking_id'],
                'status' => Campaign::CAMPAIGN_TRACKING_FAILED_STATUS,
                'message' => \GuzzleHttp\json_encode($payload['message'])
            ];
            $result = CampaignTrackingLog::create($response);
        } catch (\Exception $exception) {
            $result = $exception->getMessage();
        }
        return $result;
    }

    /**
     * saving tracking logs of success email,
     */
    protected function savetrackingEmailLogs($payload, $trackingKey)
    {
        try {
            $this->updateCompleteTrackingStatus($trackingKey, Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS);
            $response = [
                'campaign_tracking_id' => $payload['campaign_tracking_id'],
                'status' => Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS,
                'message' => $payload['message']
            ];
            $result = CampaignTrackingLog::create($response);
        } catch (\Exception $exception) {
            $result = $exception->getMessage();
        }
        return $result;
    }

    /**
     * updating the campaign tracking status from executing to complete,
     */
    protected function updateCompleteTrackingStatus($trackingKey, $status)
    {
        $campaignTrackingResponse = CampaignTracking::where('track_key', $trackingKey)->update([
            'status' => $status,
            'sent' => 1,
            'sent_at' => Carbon::now(),
            'ended_at' => Carbon::now()
        ]);
        return $campaignTrackingResponse;
    }

    protected function savetrackingLogs($payload, $trackingKey)
    {
        try {
            $this->updateCompleteTrackingStatus($trackingKey, Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS);
            $response = [
                'campaign_tracking_id' => $payload['campaign_tracking_id'],
                'status' => Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS,
                'message' => $payload['message']
            ];
            $result = CampaignTrackingLog::create($response);
        } catch (\Exception $exception) {
            $result = $exception->getMessage();
        }
        return $result;
    }

    /**
     * updating the campaign tracking status from added to executing,
     */
    protected function updateTrackingStatus($trackingKey, $status)
    {
        $campaignTrackingResponse = CampaignTracking::where('track_key', $trackingKey)->update([
            'status' => $status,
            'started_at' => Carbon::now()
        ]);
        return $campaignTrackingResponse;
    }
}
