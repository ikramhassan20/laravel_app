<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 2/21/19
 * Time: 5:23 PM
 */

namespace app\Components\Campaigns;


class CampaignActionTriggerValidatorRequest
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
        //$rules['code'] = 'required';
//        $rules['value'] = 'required';
        return $rules;
    }

}