<?php

namespace App\Http\Controllers;

use App\AppUsers;
use App\Cache\CacheKeys;
use App\Jobs\ExportUsersJob;
use App\Notifications\ExportUsersNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Excel;

class TestController extends Controller
{
    public function index()
    {

    }
}
