<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CommonHelper;
use App\Http\Controllers\NotificationController;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class ApiNotificationController extends Controller
{
    public function sendFcmNotification()
    {
        $tokens = array(
            'dWZF_0K9PeM:APA91bE1mHRQ3BE590xDtgPkjO9DXND3xpkLnKgfy5sATqQ6F5M4jbEM9lmXLVFi4aJ75rN0UWP2344LkPaYda4P-wv06s-jF46cLkGJF5RfoQIab5GNhrq5jieeu9qO9U9UVNVyB-q0',
            'aWZF_0K9PeM:APA91bE1mHRQ3BE590xDtgPkjO9DXND3xpkLnKgfy5sATqQ6F5M4jbEM9lmXLVFi4aJ75rN0UWP2344LkPaYda4P-wv06s-jF46cLkGJF5RfoQIab5GNhrq5jieeu9qO9U9UVNVyB-q0',
            'pWZF_0K9PeM:APA91bE1mHRQ3BE590xDtgPkjO9DXND3xpkLnKgfy5sATqQ6F5M4jbEM9lmXLVFi4aJ75rN0UWP2344LkPaYda4P-wv06s-jF46cLkGJF5RfoQIab5GNhrq5jieeu9qO9U9UVNVyB-q0',
            'eVYEpgmRGWA:APA91bGT2X9UWcEfRNEWdBPRo6TNkQIhF3gc3Zz6H5rEorM3x8cD_QASuHe9dZrjU4kcOPiUKj2kVH1FBdbLcMUAGw6dWVp5IZgEbMT35ikEna-QRMOjYpOUrRSpp5kpYqQr7hro5Zvw',
            'eZHvOJL-nNs:APA91bFNGRCHfEvN-Z-m33qfOZSq2aNcYOusl2dVELSNjwRWIVoF1FV8OrudO1pS9AfqCBmHkUbnZMwRfs2S_sIuNBgaJ1NOv9eIMNmcILtG4RQoMc2DwJKyeNkL55IrRsCwJryjJgIS'
        );

        $androidServekey = 'AAAAGOFAepI:APA91bHCNaJ6KAOFvivnQcCcbLfouFud56KSoLvuuGjWSFlvHu6-3tFSqd5F8ZMKlfj6UXpi6yDLGXo3QdKLdnk56Z3yY2lFn2uzIkk5bITzhy51hOKVXHSJ3VCd2oAj-T6bxVJxfP3e';
        $iosServekey = 'AAAAHtc5HBo:APA91bH3hvkYhGYPzJ9vdFETqXKwBFJShdMExDbMbp4BYpNGAhZEq-r7H0QjYkTVGMPCqA0qkpJVxpkBiCOpFiGNVgRiHdmvTEIqbO-qWx7d36kfvPDzvHs0CDnv-1suENbVVSz7dl1oYwavUtude4g-8A-hyWPtHQ';


        \Artisan::call('config:cache');
        \Config::set('fcm.http.server_key', $androidServekey);


        $notification = array(
            'title' => 'Testing Push',
            'body' => 'heyGuys',
            'link' => "coredirection://bookingdetailpage?class_id=84",
        );
        $data = array(
            "data" => array(
                "backgroundColor" => "#FFFFFF",
                "message_position" => "top",
                "api_key" => "",
                "device_type" => "android",
                "device_token" => '',
                "campaign_code" => '',
                "user_id" => '1770',
                "track_key" => ['7BooXVm6qEsF6bB6TqEH','7BooXVm6qEsF6bB6TqEH'],
                "action_url"=>"https://www.google.com",
                "is_hermis_platform" => true,
                "is_silent" => false,
                "campaign_type" => "push",
                "message_type" => "dialogue",
                "priority" => "normal",
                "icon" => "",
                "params" => [
                    "deep link" => "coredirection://bookingdetailpage?class_id=84",
                ],
                "message" => "<h1>Test for Ios Push</h1><p>hi how are you</p>",
                "view_link" => "https://www.google.com"
            )
        );

        $notifications = new NotificationController($tokens,$notification,$data,$androidServekey,0);
        $response = $notifications->sendNotification();
        if(!empty($response['tokensToDelete']))
        {
           $result=CommonHelper::updateDeviceToken($response['tokensToDelete'],[
                'is_revoked'=>'1',
                'status'=>'0',
                'deleted_at' => Carbon::now()
            ]);
        }
        dd($response);
    }

}
