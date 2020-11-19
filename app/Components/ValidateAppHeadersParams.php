<?php

namespace App\Components;

use App\Apps;
use App\AppUsers;

trait ValidateAppHeadersParams
{
    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $params;

    /**
     * Validate app request data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function validateAppRequest(\Illuminate\Http\Request $request)
    {
        $data = $this->parseResponse($request);

        $errors = array_flatten(array_filter([
            'headers' => $this->checkAppHeaders($request),
            'params' => $this->checkAppParams($data),
        ]));

        if (!empty($errors)) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                $errors,
                'error'
            );
        }

        $data = array_filter($data);
        $this->params = array_merge($data, $this->compileAppHeaders($request, true));

        return [];
    }

    protected function validateBulkAppRequest(\Illuminate\Http\Request $request, $params)
    {
        $data = $this->parseResponse($request);

        $errors = array_flatten(array_filter([
            'headers' => $this->checkAppHeaders($request),
            'params' => $this->checkAppParams($params),
        ]));

        if (!empty($errors)) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                $errors,
                'error'
            );
        }
        //  $data = array_filter($params);
        $this->params = array_merge($params, $this->compileAppHeaders($request, true));
        return [];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function checkAppHeaders(\Illuminate\Http\Request $request)
    {
        $headers = $this->compileAppHeaders($request);

        $apps = Apps::join('app_group', "app_group.id", "=", "app.app_group_id")
            ->where("app_group.company_id", $request->user()->id)
            ->where("app.platform", $headers["device-type"])
            ->where("app.app_id", $headers["app-id"])
            ->select("app.*", "app_group.company_id", "app_group.is_default", "app_group.created_by", "app_group.updated_by")
            ->get();

        if (count($apps) > 0) {
            $app_ids = $apps->pluck('app_id')->unique()->toArray();
            $app_names = $apps->pluck('name')->unique()->toArray();
        } else {
            throw new \Exception("No apps created");
        }

        $rules = [];
        foreach (config('engagement.api.headers.app') as $header) {
            if (!empty($skip) && in_array($header, $skip)) {
                continue;
            }

            $rule = [];
            $rule[] = ($header === 'user-token') ? 'filled' : 'required';

            if ($header === 'device-type') {
                $rule[] = 'in:' . implode(',', config('engagement.api.notifications.device_types'));
            } elseif (in_array($header, ['app-id'])) {
                $rule[] = 'in:' . implode(',', $app_ids);
            } elseif (in_array($header, ['app-name'])) {
                $rule[] = 'in:' . implode(',', $app_names);
            }

            $rules[$header] = implode('|', $rule);
        }

        $validator = \Validator::make($headers, $rules);

        return ($validator->fails()) ? $validator->errors()->all() : [];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param bool $transform
     *
     * @return array
     */
    protected function compileAppHeaders(\Illuminate\Http\Request $request, $transform = false)
    {
        $headers = collect($request->headers)->filter(function ($value, $key) {
            return in_array($key, config('engagement.api.headers.app')) ? $value : null;
        });

        if ($transform === true) {
            return $headers->flatMap(function ($value, $key) {
                return [
                    str_replace('-', '_', $key) => implode($value)
                ];
            })->filter()->toArray();
        }

        return $headers->transform(function ($value) {
            return implode($value);
        })->toArray();
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    protected function checkAppParams($params)
    {
        if($params['mode'] == AppUsers::USER_LOGIN){
            $rules = [
                'user_id' => 'required|integer|min:1'
            ];
        }
        elseif($params['mode'] == AppUsers::USER_LOGOUT){
            $rules = [
                'user_id' => 'required|integer|min:1',
                'user_token' => 'required'
            ];
        }
        else{
            $rules = [
                'user_id' => 'required|integer|min:1',
                'email' => 'filled|email',
            ];
        }

        $validator = \Validator::make($params, $rules);

        return ($validator->fails()) ? $validator->errors()->all() : [];
    }
}
