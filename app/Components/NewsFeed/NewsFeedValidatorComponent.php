<?php

namespace app\Components\NewsFeed;


class NewsFeedValidatorComponent
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
        $rules['longitude'] = 'required';
        $rules['latitude'] = 'required';
        return $rules;
    }
}