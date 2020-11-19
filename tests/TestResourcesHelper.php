<?php

namespace Tests;

use App\Components\DatabaseFactory;
use App\User;

trait TestResourcesHelper
{
    protected static $user;
    protected static $item;
    protected static $authToken;

    /**
     * Login test company.
     */
    protected static function loginCompany()
    {
        self::$user = User::where('email', 'company@engagement.com')->firstOrFail();
        self::$authToken = self::$user->api_token;
    }

    /**
     * @param string $resource
     * @param array  $data
     */
    protected static function createResource($resource, $data)
    {
        self::$item = DatabaseFactory::create($resource, $data);
    }
}