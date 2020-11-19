<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubscribeController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('users');
    }

    public function store(Request $request)
    {
        return $this->resourceClass->syncUser($request);
    }

    public function bulkUserImport(Request $request)
    {
        return $this->resourceClass->bulkUserImport($request);
    }
}
