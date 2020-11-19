<?php

namespace App\Http\Controllers;

use App\Components\PayloadNotification;
use App\Components\PayloadNotificationDataBinding;
use App\Http\FcmNotification\SendNotification;
use Illuminate\Http\Request;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use GuzzleHttp\Client as HttpClient;

class NotificationController extends Controller
{
    //
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

    /**
     * @var array
     */
    protected $contentAvailable;


    public function __construct($device_token,$notification,$data,$apiKey,$contentAvailable)
    {

        $this->device_token = $device_token;
        $this->apiKey = $apiKey;
        $this->notification = $notification;
        $this->data = $data;
        $this->contentAvailable = $contentAvailable;
    }

    public function sendNotification()
    {
        try {
            $url='https://fcm.googleapis.com/fcm/send';

            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60 * 20);
            $optionBuilder->setContentAvailable($this->contentAvailable);

            $notificationBuilder = new PayloadNotification((isset($this->notification['title'])) ? $this->notification['title'] : "");
            $notificationBuilder->setBody((isset($this->notification['body'])) ? $this->notification['body'] : "")
                ->setSound('')
                ->setIcon('')
                ->setChannelId('')
                ->setClickAction('')
                ->setColor('')
                ->setBadge('')
                ->setTitleLocationArgs('')
                ->setLink((isset($this->notification['link'])) ? $this->notification['link'] : "");

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData($this->data);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();


            $data = $dataBuilder->build();

            $token= $this->device_token;


            $downstreamResponse = (new SendNotification(new HttpClient(), $url))->sendTo($token, $option, $notification, $data, $this->apiKey);


            $downstreamResponse->numberSuccess();
            $downstreamResponse->numberFailure();
            $downstreamResponse->numberModification();
            //return Array - you must remove all this tokens in your database
            $downstreamResponse->tokensToDelete();
            //return Array (key : oldToken, value : new token - you must change the token in your database )
            $downstreamResponse->tokensToModify();
            //return Array - you should try to resend the message to the tokens in the array
            $downstreamResponse->tokensToRetry();

            $downstreamResponse->tokensWithError();
            // return Array (key:token, value:errror) - in production you should remove from your database the token

            $finalarray = array(
                'numberSuccess' => $downstreamResponse->numberSuccess(),
                'numberFailure' => $downstreamResponse->numberFailure(),
                'numberModification' => $downstreamResponse->numberModification(),
                'tokensToDelete' => $downstreamResponse->tokensToDelete(),
                'tokensToModify' => $downstreamResponse->tokensToModify(),
                'tokensToRetry' => $downstreamResponse->tokensToRetry(),
                'tokensWithError' => $downstreamResponse->tokensWithError()
            );
        } catch (\Exception $exception) {
            $finalarray = array(
                'status' => $exception->getCode(),
                'error' => $exception->getMessage(),
                'message' => $exception->getMessage()
            );
        }
        return $finalarray;
    }
//    public function sendGroupNotification()
//    {
//        try {
//            $optionBuilder = new OptionsBuilder();
//            $optionBuilder->setTimeToLive(60 * 20);
//
//            $notificationBuilder = new PayloadNotificationBuilder('my title');
//            $notificationBuilder->setBody('Hello world')
//                ->setSound('default');
//
//            $dataBuilder = new PayloadDataBuilder();
//            $dataBuilder->addData(['a_data' => '<h2>hey<body>test</body>', 'user_id' => '27']);
//
//            $option = $optionBuilder->build();
//            $notification = $notificationBuilder->build();
//            $data = $dataBuilder->build();
//
//            $token = $this->device_token;
//
//            $groupResponse = FCM::sendToGroup($token, $option, null, $data);
//
//            $groupResponse->numberSuccess();
//            $groupResponse->numberFailure();
//            $groupResponse->tokensFailed();
//            $groupResponse->tokensFailed();
//            $finalarray = array(
//                'numberSuccess' => $groupResponse->numberSuccess(),
//                'numberFailure' => $groupResponse->numberFailure(),
//                'tokensFailed' => $groupResponse->tokensFailed(),
//                'groupResponse' => $groupResponse
//            );
//        } catch (\Exception $exception) {
//            $finalarray = array(
//                'status' => 400,
//                'message' => $exception
//            );
//        }
//        return $finalarray;
//    }
}
