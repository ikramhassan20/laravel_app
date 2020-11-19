<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 3/8/19
 * Time: 6:08 PM
 */

namespace app\Components\Campaigns;


class CampaignTrackingServiceValidatorRequest
{
    public $errors;
    protected $params;

    public function __construct($params)
    {
        $this->params = $params;

        $this->validate();
    }

    protected function validate()
    {
        $rules = $this->rules();

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
        $rules['user_id'] = 'required';
        $rules['track_key'] = 'required';
        $rules['mode'] = 'required';
        return $rules;
    }
}