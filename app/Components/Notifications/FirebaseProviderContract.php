<?php

namespace App\Components\Notifications;

interface FirebaseProviderContract
{
    /**
     * Set Firebase Server API Key.
     *
     * @param string $apiKey
     *
     * @return void
     */
    public function setApiKey($apiKey);

    /**
     * Set notification payload for Firebase.
     *
     * @param array $params
     *
     * @return void
     */
    public function setNotification($params);

    /**
     * Set data payload for Firebase.
     *
     * @param array $params
     *
     * @return void
     */
    public function setData($params);

    /**
     * Set recipients.
     *
     * @param array $params
     *
     * @return void
     */
    public function setRecipients($params);

    /**
     * @return mixed
     */
    public function send();
}
