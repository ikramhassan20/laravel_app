<?php

namespace App\Http\Resources\V1;

use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use Illuminate\Http\Request;

class StatsResource
{
    use ParseResponse;

    public function all(Request $request)
    {
        try {
            $class = new Stats\StatsSummary();

            $response = $class->summary($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data',
                $response
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        try {
            $class = new Stats\StatsSummary();

            $response = $class->process($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load stats data'],
                'error'
            );
        }
    }

    public function emailCampaign(Request $request)
    {
        try {
            $class = new Stats\StatsSummary();

            $response = $class->emailCampaign($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load stats data'],
                'error'
            );
        }
    }

    public function conversationCampaign(Request $request)
    {
        try {
            $class = new Stats\StatsSummary();

            $response = $class->conversationCampaign($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load stats data'],
                'error'
            );
        }
    }

    public function getUsers($type, $deviceType)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getUsers($type, $deviceType);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function getCampaignStatsCount($request)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getCampaignStatsCount($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function getConversionCount($request)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getConversionCount($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load conversion stats'],
                'error'
            );
        }
    }

    public function getNewsFeedCount($request)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getNewsFeedCount($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                ['Unable to load newsfeed stats'],
                'error'
            );
        }
    }

    public function getUserStatsCount($request)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getUserStatsCount($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    public function getRecentApps($request)
    {
        try {

            $class = new Stats\StatsSummary();

            $response = $class->getRecentApps($request);

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response,
                'data'
            );
        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }
}