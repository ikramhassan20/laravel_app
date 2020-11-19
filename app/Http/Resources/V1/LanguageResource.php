<?php

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\AppStatusMessages;
use App\Components\ParseResponse;
use App\Components\RenderLanguagePaginatedResponse;
use App\Http\Resources\ResourcesSteps;
use App\Http\Resources\Contracts\ResourcesContract;
use App\Http\Resources\Contracts\ProcessResourceDataContract;
use App\Language;
use App\Translation;
use Illuminate\Support\Carbon;

class LanguageResource implements ResourcesContract, ProcessResourceDataContract
{

    use ParseResponse, ResourcesSteps, RenderLanguagePaginatedResponse;

    public function all(\Illuminate\Http\Request $request)
    {
        try {
            $response = $this->languagePaginateResponse(Language::class, $request);
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $response['data'],
                'data',
                $response['meta']
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to Language data'],
                'error'
            );
        }

    }

    public function create(\Illuminate\Http\Request $request)
    {
        try {
            $language = $this->process($request, new Language());
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $language,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }

    }

    public function process($request, \Illuminate\Database\Eloquent\Model $model)
    {
        $user = $request->user();
        $data = $this->parseResponse($request);
        if (!isset($model->id)) {
            $result = $model->where('name', '=', $data['name'])->get();
            if (count($result) > 0) {
                $model = 1;
            } else {
                $model->create($data);
            }
        } else {
            $model->update($data);
        }
        return $model;
    }

    public function edit($id)
    {
        try {
            $language = Language::where('id', $id)->first();
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $language,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                ['Unable to create language'],
                'error'
            );
        }
    }

    public function update(\Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {

            $language = $this->process($request, $model);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                AppStatusMessages::SUCCESS,
                $language,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function removeLanguage($request, \Illuminate\Database\Eloquent\Model $model)
    {
        try {
            $translationCheck = Translation::where('language_id', '=', $model->id)->first();
            if (!$translationCheck) {
                if ($model->code == 'en') {
                    throw new \Exception('English is default language can not be deleted');
                }
                $languageResponse = Language::where('id', '=', $model->id)->update([
                    'deleted_at' => Carbon::now()
                ]);
                if ($languageResponse) {
                    return $this->addResponse(
                        AppStatusCodes::HTTP_OK,
                        AppStatusMessages::SUCCESS,
                        $model,
                        'data'
                    );
                } else {
                    throw new \Exception('Language is not deleted');
                }
            } else {
                throw new \Exception('This language is exist in translation');
            }
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

}