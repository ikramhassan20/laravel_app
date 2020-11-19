<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SendController extends Controller
{
    public function __construct()
    {
        $this->setResourceClass('notifications');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['message'] = base64_decode($request['message']);
        $request['html_content'] = base64_decode($request['html_content']);
        return $this->resourceClass->process($request, new Notification());
    }
}
