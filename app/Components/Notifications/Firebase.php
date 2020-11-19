<?php

namespace App\Components\Notifications;

use App\Components\AppStatusCodes;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException as HttpBadRequestException;

class Firebase implements FirebaseProviderContract
{
    /**
     * @var string
     */
    protected $apiKey;

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
    protected $recipients;

    /**
     * Set Firebase Server API Key.
     *
     * @param string $apiKey
     *
     * @return void
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Set notification payload for Firebase.
     *
     * @param array $params
     *
     * @return void
     */
    public function setNotification($params)
    {
        $this->notification = $params;
    }

    /**
     * Set data payload for Firebase.
     *
     * @param array $params
     *
     * @return void
     */
    public function setData($params)
    {
        $this->data = $params;
    }

    /**
     * Set recipients.
     *
     * @param array|string $params
     *
     * @return void
     */
    public function setRecipients($params)
    {
        $this->recipients = $params;
    }

    public function send()
    {
        try {
            $client = new HttpClient();

            $recipientKey = is_array($this->recipients) ? 'registration_ids' : 'to';

            $fields = array_filter([
                $recipientKey => $this->recipients,
                'notification' => $this->notification,
                'data' => $this->data,
            ]);

            $request = $client->post('https://fcm.googleapis.com/fcm/send', [
                'json' => $fields,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'key=' . $this->apiKey,
                ]
            ]);

            $response = \GuzzleHttp\json_decode($request->getBody()->getContents(), true);
            if (isset($response['success']) && ((bool)$response['success'] === true)) {
                return [
                    'code' => AppStatusCodes::HTTP_OK,
                    'message' => 'Notification Send Successfully'
                ];
            }else{
                return [
                    'code' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => $response['results']
                ];
            }
        } catch (HttpBadRequestException $exception) {
            throw new \Exception($exception->getMessage());
        } catch (\Exception $exception) {
            return [
                'code' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $exception->getMessage()
            ];
        }
    }
}
