<?php

namespace App\Http\Resources\V1\Notifications;

class ValidateRequest
{
    protected $params;
    public $errors;

    public function __construct($params)
    {
        $this->params = $params;

        $this->validate();
    }

    protected function validate()
    {
        $rules = $this->rules($this->params['type']);
        $validator = \Validator::make($this->params, $rules);
        if ($validator->fails()) {
            $this->errors = $validator->errors()->all();
        } else {
            $this->errors = [];
        }
    }
    protected function rules()
    {
        $rules = [];

        if (!isset($this->params['row_id'])) {
//            $rules['filter_type'] = 'required|in:user_id,email';
            $rules['items'] = 'required|array';
        } else {
            $rules['row_id'] = 'required|array';
        }

        $rules['message'] = 'sometimes|string';
        $rules['type'] = 'required|in:' . implode(',', config('engagement.api.notifications.notification_types'));

        if (isset($this->params['type']) && !in_array($this->params['type'], ['email'])) {
            $rules['platform'] = 'required|in:' . implode(',', config('engagement.api.notifications.platforms'));

            if (in_array($this->params['type'], ['inapp'])) {
                $rules['message_type'] = 'required|in:' . implode(',', config('engagement.api.notifications.inapp_types'));

                if (isset($this->params['message_type']) && in_array($this->params['message_type'], ['dialogue'])) {
                    $rules['message_position'] = 'required|in:' . implode(',', config('engagement.api.notifications.inapp_dialogue_types'));
                }
            }
        }
        if (isset($this->params['type']) && in_array($this->params['type'], ['email'])) {
            $rules['email'] = 'required|array';
        }

        return $rules;
    }
}