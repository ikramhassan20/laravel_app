<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 3/4/19
 * Time: 11:46 AM
 */

namespace app\Components\Campaigns;


class CampaignConversionTriggerValidatorRequest
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
        $rules = $this->rules();

        $validator = \Validator::make($this->params, $rules);
        if ($validator->fails()) {
            $this->errors = $validator->errors()->all();
        } else {
            $this->errors = [];
        }
    }
    protected function rules(){
        $rules = [];
        $rules['user_id'] = 'required';
        $rules['track_key'] = 'required|array';
        $rules['code'] = 'required';
        return $rules;
    }
}