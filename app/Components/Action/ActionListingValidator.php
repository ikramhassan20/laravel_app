<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 3/13/19
 * Time: 3:30 PM
 */

namespace app\Components\Action;


class ActionListingValidator
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
        $rules['data_type'] = 'required';
        return $rules;
    }

}