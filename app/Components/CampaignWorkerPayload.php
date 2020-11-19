<?php

namespace App\Components;

/**
 * Class CampaignWorkerPayload
 * @package App\Components
 * @todo build and generate the campaign worker payload
 */
class CampaignWorkerPayload
{
    /**
     * generate worker payload notification
     *
     * @param array $params
     *
     * @return array $notification
     */
    public static function generatePayloadNotification($params)
    {
        // generates payload for push
        $notification = array(
                    'title' => (isset($params['title'])) ? $params['title'] : "",
                    'body' => (isset($params['body'])) ? $params['body'] : "", // $params['message']
                    'link' => (isset($params['link'])) ? $params['link'] : "" // $params['deep_link']
                );

        return $notification;
    }

    /**
     * generate worker payload data
     *
     * @param array $params
     *
     * @return array $notification
     */
    public static function generatePayloadData($params)
    {
        $data = array(
            "data" => array(
                "title" => (isset($params['title'])) ? $params['title'] : "",
                "body" => (isset($params['body'])) ? $params['body'] : "",
                "backgroundColor" => (isset($params['backgroundColor'])) ? $params['backgroundColor'] : "#FFFFFF",
                "message_position" => $params['message_position'],
                "device_type" => $params['device_type'], // remove later on
                "device_token" => $params['device_token'], // remove later on
                "app_group_id" => $params['app_group_id'],
                "code" => (isset($params['code'])) ? $params['code'] : "",
                "user_id" => (isset($params['user_id'])) ? $params['user_id'] : "",
                "track_key" => (isset($params['track_keys'])) ? $params['track_keys'] : [],
                "action_url" => (isset($params['action_url'])) ? $params['action_url'] : "",
                "is_hermis_platform" => $params['is_hermis_platform'],
                "is_silent" => $params['is_silent'],
                "type" => $params['type'],
                "message_position" => $params['message_position'],
                "message_type" => (isset($params['message_type'])) ? $params['message_type'] : "",
                "priority" => $params['priority'],
                "icon" => ($params['icon'] != "") ? $params['icon'] : "",
                "auto_close" => $params['auto_close'],
                "params" => [
                    (isset($params['action_type'])) ? $params['action_type'] : "deep link" => (isset($params['action_value'])) ? $params['action_value'] : "",
                ],
                "dispatch_date" => $params['dispatch_date'],
                "view_link" => $params['view_link'],
                "is_board" => (isset($params['is_board'])) ? (bool)$params['is_board'] : false
            )
        );

//        $data = array(
//            "data" => array(
//                "backgroundColor" => "#FFFFFF",
//                "message_position" => "top",
//                "api_key" => "",
//                "device_type" => "ios",
//                "device_token" => '',
//                "campaign_code" => '',
//                "user_id" => '55',
//                "track_key" => ['7BooXVm6qEsF6bB6TqEH','122'],
//                "action_url"=>"https://www.google.com",
//                "is_hermis_platform" => true,
//                "is_silent" => false,
//                "campaign_type" => "inapp",
//                "message_type" => "dialogue",
//                "priority" => "normal",
//                "icon" => "",
//                "params" => [
//                    "deep link" => "coredirection://bookingdetailpage?class_id=84",
//                ],
//                "message" => "<h1>Test for Android Push</h1><p>hi how are you</p>",
//                "view_link" => "https://www.google.com"
//            )
//        );

        return $data;
    }

    /**
     * generate worker payload data
     *
     * @param array $params
     *
     * @return array $notification
     */
    public static function generateBoardPayloadData($params)
    {
        $data = array(
            "data" => array(
                "title" => (isset($params['title'])) ? $params['title'] : "",
                "body" => (isset($params['body'])) ? $params['body'] : "",
                "backgroundColor" => (isset($params['backgroundColor'])) ? $params['backgroundColor'] : "#FFFFFF",
                "message_position" => $params['message_position'],
                "device_type" => $params['device_type'], // remove later on
                "device_token" => $params['device_token'], // remove later on
                "code" => (isset($params['code'])) ? $params['code'] : "",
                "user_id" => (isset($params['user_id'])) ? $params['user_id'] : "",
                "track_key" => (isset($params['track_keys'])) ? $params['track_keys'] : [],
                "action_url" => (isset($params['action_url'])) ? $params['action_url'] : "",
                "is_hermis_platform" => $params['is_hermis_platform'],
                "is_silent" => $params['is_silent'],
                "type" => $params['type'],
                "message_position" => $params['message_position'],
                "message_type" => (isset($params['message_type'])) ? $params['message_type'] : "",
                "priority" => $params['priority'],
                "icon" => ($params['icon'] != "") ? $params['icon'] : "",
                "auto_close" => $params['auto_close'],
                "params" => [
                    (isset($params['action_type'])) ? $params['action_type'] : "deep link" => (isset($params['action_value'])) ? $params['action_value'] : "",
                ],
                "dispatch_date" => $params['dispatch_date'],
                "view_link" => $params['view_link'],
                "is_board" => (isset($params['is_board'])) ? (bool)$params['is_board'] : true
            )
        );

//        $data = array(
//            "data" => array(
//                "backgroundColor" => "#FFFFFF",
//                "message_position" => "top",
//                "api_key" => "",
//                "device_type" => "ios",
//                "device_token" => '',
//                "campaign_code" => '',
//                "user_id" => '55',
//                "track_key" => ['7BooXVm6qEsF6bB6TqEH','122'],
//                "action_url"=>"https://www.google.com",
//                "is_hermis_platform" => true,
//                "is_silent" => false,
//                "campaign_type" => "inapp",
//                "message_type" => "dialogue",
//                "priority" => "normal",
//                "icon" => "",
//                "params" => [
//                    "deep link" => "coredirection://bookingdetailpage?class_id=84",
//                ],
//                "message" => "<h1>Test for Android Push</h1><p>hi how are you</p>",
//                "view_link" => "https://www.google.com"
//            )
//        );

        return $data;
    }

    /**
     * generate worker payload for  Push
     *
     * @param array $notification
     * @param array $data
     *
     * @return array $payload
     */
    public static function generateWorkerPayloadSandbox($notification, $data){

        if($notification == null){
            $payload = array(
                'data' => $data['data']
            );
        }
        else{
            $payload = array(
                "notification" => $notification,
                'data' => $data['data']
            );
        }

        return $payload;
    }

    /**
     * generate worker payload for  Push
     *
     * @param array $params
     *
     * @return array $payload
     */
    public static function generateWorkerPushPayload($params)
    {
        // generates payload for push
        $payload = array(
            "notification" => array("title" => $params['title'],
                "body" => $params['body'],
                "icon" => $params['icon']
            ),
            "data" => [
                "api_key" => $params['api_key'],
                "device_type" => strtoupper($params['device_type']),
                "device_token" => $params['device_token'],
                "campaign_code" => $params['campaign_code'],
                "user_id" => $params['user_id'],
                "track_key" => $params['track_key'],
                "is_hermis_platform" => ($params['is_hermis_platform'] == true) ? (boolean)$params['is_hermis_platform'] : false,
                "is_silent" => ($params['is_silent'] == true) ? (boolean)$params['is_silent'] : false,
                "campaign_type" => $params['campaign_type'],
                "message_type" => $params['message_type'],
                "priority" => $params['priority'],
                "params" => [
                    $params['action_type'] => $params['action_value'],
                ],
                "data" => "success"
            ]
        );

        return $payload;
    }

    /**
     * generate worker payload for In-App
     *
     * @param array $params
     *
     * @return array $payload
     */
    public static function generateWorkerInAppPayload($params)
    {
        // generates payload for in-app
        $payload = array(
            "data" => [
                "notification" => array("title" => $params['title'],
                    "body" => $params['body'],
                    "icon" => $params['icon']
                ),
                "api_key" => $params['api_key'],
                "device_type" => strtoupper($params['device_type']),
                "device_token" => $params['device_token'],
                "backgroundColor" => $params['backgroundColor'],
                "campaign_code" => $params['campaign_code'],
                "is_hermis_platform" => ($params['is_hermis_platform'] == true) ? (boolean)$params['is_hermis_platform'] : false,
                "is_silent" => ($params['is_silent'] == true) ? (boolean)$params['is_silent'] : false,
                "message_type" => $params['message_type'],
                "track_key" => $params['track_key'],
                "user_id" => $params['user_id'],
                "campaign_type" => $params['campaign_type'],
                "message_position" => $params['message_position'],
                "message" => $params['message'],
                "view_link" => $params['view_link'],
                "priority" => $params['priority'],
                "data" => "success"
            ]
        );

        return $payload;
    }

    /**
     * generate worker payload for  Push
     *
     * @param array $params
     *
     * @return array $payload
     */
    public static function generateAndroidWorkerPushPayload($params)
    {
        // generates payload for push
        $payload = array(
            "data" => [
                "apiKey" => $params['api_key'],
                "notification" => [
                    "body" => $params['body'],
                    "title" => $params['title'],
                    "link" => $params['action_value'],
                    "backgroundColor" => $params['backgroundColor'],
                    "icon" => $params['icon']
                ],
                "android"  => [
                    "priority" => $params['priority']
                ],
                "priority" => 5,
                "alert" => [
                    "data" => $params['body'],
                    "device_type" => strtoupper($params['device_type']),
                    "device_token" => $params['device_token'],
                    "message_type" => $params['message_type'],
                    "position" => $params['message_position'],
                    "campaign_code" => $params['campaign_code'],
                    "campaign_type" => $params['campaign_type'],
                    "user_id" => $params['user_id'],
                    "track_key" => $params['track_key'],
                    "is_hermis_platform" => ($params['is_hermis_platform'] == true) ? (boolean)$params['is_hermis_platform'] : false,
                    "is_silent" => ($params['is_silent'] == true) ? (boolean)$params['is_silent'] : false,
                    "view_link" => $params['view_link'],
                    "backgroundColor" => $params['backgroundColor'],
                    "icon" => $params['icon'],
                    "params" => [
                        $params['action_type'] => $params['action_value'],
                    ]
                ]
            ]
        );

        return $payload;
    }

    /**
     * generate worker payload for In-App
     *
     * @param array $params
     *
     * @return array $payload
     */
    public static function generateAndroidWorkerInAppPayload($params)
    {
        // generates payload for in-app
        $payload = array(
            "data" => [
                "apiKey" => $params['api_key'],
                "android" => [
                    "priority" => $params['priority']
                ],
                "priority" => "5",
                "alert" => [
                    "data" => $params['message'],
                    "message_type" => $params['message_type'],
                    "position"  => $params['message_position'],
                    "campaign_code" => $params['campaign_code'],
                    "campaign_type" => $params['campaign_type'],
                    "user_id" => $params['user_id'],
                    "track_key" => $params['track_key'],
                    "is_silent" => ($params['is_silent'] == true) ? (boolean)$params['is_silent'] : false,
                    "is_hermis_platform" => ($params['is_hermis_platform'] == true) ? (boolean)$params['is_hermis_platform'] : false,
                    "view_link" => $params['view_link'],
                    "backgroundColor" => $params['backgroundColor'],
                    "device_type" => strtoupper($params['device_type']),
                    "device_token" => $params['device_token'],
                    "icon" => $params['icon']
                ]
            ]
        );

        return $payload;
    }

    /**
     * generate worker payload for Email
     *
     * @param array $params
     *
     * @return array $payload
     */
    public static function generateWorkerEmailPayload($params)
    {
        // generate worker payload for Email
        $payload = array(
            "data" => [
                "email_body" => $params['template_content'],
                "email_subject" => $params['subject'],
                "email_from" => $params['from_email'],
                "to_email" => $params['to_email'],
                "priority" => $params['priority'],
                "data" => "success"
            ]
        );
        return $payload;
    }
}
