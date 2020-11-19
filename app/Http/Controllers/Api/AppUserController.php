<?php

namespace App\Http\Controllers\Api;

use App\AppGroup;
use App\Apps;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppUserController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('app_users');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    public function toggleNotification(Request $request)
    {
        return $this->resourceClass->notificationToggle($request);
    }

    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }
}

