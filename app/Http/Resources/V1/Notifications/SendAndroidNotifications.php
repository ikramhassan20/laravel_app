<?php

namespace App\Http\Resources\V1\Notifications;

use App\CampaignTracking;
use App\Components\AppStatusCodes;
use App\Components\InteractsWithMessages;
use App\Components\Notifications\Firebase;
use App\Helpers\CommonHelper;
use App\Notification;
use App\NotificationsLog;
use Carbon\Carbon;

class SendAndroidNotifications
{
    use InteractsWithMessages;
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $device_token;

    /**
     * @var string
     */
    protected $device_type;

    /**
     * @var array
     */
    protected $notification;

    /**
     * @var array
     */
    protected $data;

    public function __construct($recipients)
    {

        //dd($recipients);
        $this->apiKey = "AAAAGOFAepI:APA91bHCNaJ6KAOFvivnQcCcbLfouFud56KSoLvuuGjWSFlvHu6-3tFSqd5F8ZMKlfj6UXpi6yDLGXo3QdKLdnk56Z3yY2lFn2uzIkk5bITzhy51hOKVXHSJ3VCd2oAj-T6bxVJxfP3e"; //$recipients['data']['apiKey'];
        $this->device_type = $recipients['data']['alert']['device_type'];
        $this->device_token = $recipients['data']['alert']['device_token'];
        if ($recipients['data']['alert']['campaign_type'] == 'inapp') {
            $this->notification = isset($recipients['data']['notification']) ? $recipients['data']['notification'] : null;
            //$this->notification = null;
        } else {
            $this->notification = isset($recipients['data']['notification']) ? $recipients['data']['notification'] : null;
        }
        $this->data = isset($recipients['data']['alert']) ? $recipients['data']['alert'] : null;
        //dd($this->notification);
    }

    /**
     * Send notification.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function send()
    {
        $finalResponse=array();
        $params = [];
        if (isset($this->notification)) {
            $params['notification'] = $this->notification;
        }

        if (isset($this->data)) {
            $params['alert'] = $this->data;
        }

        //$notification = new Notification();
        $_data['platform'] = $this->device_type;
        $_data['device_token'] = $this->device_token;
        $_data['payload'] = \GuzzleHttp\json_encode($params,true);
        $_notification = new Notification();
        $_notify = $_notification->create($_data);

        $notification_id = (isset($_notify->id)) ? $_notify->id : "";
        $notification = Notification::find($notification_id);
        if (!empty($notification)) {

            // prepare and parse payload
            $payload = (isset($notification->payload)) ? $notification->payload : "";
            $payload = \GuzzleHttp\json_decode($payload);

            $_campaign_type = $payload->alert->campaign_type;
            $_track_key = $payload->alert->track_key;

            // modify payload for send test
            if($_campaign_type == "inapp" && $_track_key == ""){

                $view_link = config('engagement.url.inappview') ."notification/". $notification_id;
                //$view_link = "https://www.google.com";// ."notification/". $notification_id;
                $payload->alert->view_link = $view_link;
                $notification->payload = \GuzzleHttp\json_encode($payload);
                $notification->save();
                $this->data['view_link'] = $view_link;
                //$this->data = $payload;
                $params['alert'] = $this->data;
            }
        }

        $firebase = new Firebase();
        $firebase->setApiKey($this->apiKey);
        $firebase->setNotification($this->notification);
        $firebase->setData(["data" => $this->data]);
        $firebase->setRecipients( $this->device_token );

        // sending notification to device
        $response = $firebase->send();

        // creating object for logs
        $notificationLogs = new NotificationsLog();
        // creating object for logs
        $notificationLogs = new NotificationsLog();
        if ($response['code'] === AppStatusCodes::HTTP_OK) {
            // update notification sent status
            $notification->sent = true;
            $notification->sent_at = Carbon::now();
            $notification->save();
            // update notification logs with success
            $notificationLogs->notification_id = $notification_id;
            $notificationLogs->status = 'Success';
            $notificationLogs->message =\GuzzleHttp\json_encode($response);
            // prepare success message
            $finalResponse = array(
                'code'=>200,
                'status' => 'success',
                'data' => \GuzzleHttp\json_encode($params),
                'message' => $response['message']
            );
        }
        else {
//            CommonHelper::updateDeviceToken($this->device_token);
            // prepare error message
            $finalResponse = array(
                'code'=>400,
                'status' => 'error',
                'data' => \GuzzleHttp\json_encode($params),
                'message' =>  $response['message']
            );
            // update notification logs with error
            $notificationLogs->notification_id = $notification_id;
            $notificationLogs->status = 'error';
            $notificationLogs->message =\GuzzleHttp\json_encode($response) ;
        }

        // update logs
        $notificationLogs->save();

        // send output as response
        return $finalResponse;
    }
}