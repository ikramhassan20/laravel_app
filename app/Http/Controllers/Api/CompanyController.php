<?php

namespace App\Http\Controllers\Api;

use App\AppUsers;
use App\AppUserTokens;
use App\Cache\CacheKeys;
use App\Jobs\ExportUsersJob;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{
    //
    public function __construct()
    {
        $this->setResourceClass('company');
    }

    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    public function statusUpdate(Request $request)
    {
        return $this->resourceClass->statusUpdate($request);
    }

    public function edit(String $version, $id)
    {
        return $this->resourceClass->edit($id);
    }

    public function user(Request $request)
    {
        return $this->resourceClass->userList($request);
    }

    public function bouncedUsers(Request $request)
    {
        return $this->resourceClass->bouncedUsers($request);
    }

    public function bouncedUserDelete(Request $request, String $version, $id)
    {
        return $this->resourceClass->bouncedUserDelete($request, $id);
    }

    public function unsubscribeUser(Request $request)
    {
        return $this->resourceClass->unsubscribeUserList($request);
    }

    public function update(Request $request, String $version, User $id)
    {
        return $this->resourceClass->update($request, $id);
    }

    public function updatePassword(Request $request, String $version, User $id)
    {

        return $this->resourceClass->updatePassword($request, $id);
    }

    public function userDelete(Request $request, String $version, AppUsers $id)
    {
        return $this->resourceClass->removeUser($request, $id);
    }

    public function userStatus(Request $request, String $version, $id)
    {

        return $this->resourceClass->updateUserStatus($request, $id);
    }

    public function userStats(Request $request, String $version, $id)
    {
        return $this->resourceClass->userStats($request, $id);
    }

    public function rebuildCache(Request $request)
    {
        return $this->resourceClass->rebuildCache($request);
    }

    public function getAll(Request $request)
    {
        return $this->resourceClass->companies($request);
    }

    public function destroy($id, Request $request)
    {
        return $this->resourceClass->destroy($request);
    }

    public function exportUsers(Request $request)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
//        $company = User::find(2);
//        $group = $company->currentAppGroup();

        $cache = new CacheKeys();
        $cache_key = $cache->generateProcessExportUsersKey($group->id);
        \Cache::forget($cache_key);
        \Cache::put($cache_key, true, now()->addDays(10));

        ExportUsersJob::dispatch(\Auth::user()->id)
            ->onQueue('export_users')
            ->delay(Carbon::now()->addMinutes(1));
    }

    public function getExportUsersCsv(Request $request)
    {
        $user = $request->user();
        $group = $user->currentAppGroup();
        $companyId = $request->user()->id;
        //$company = User::find($companyId);
        //$group = $company->currentAppGroup();

        $cache = new CacheKeys();
        $cache_key = $cache->generateExportUsersKey($group->id);

        $filePath = "";
        $isFileHas = false;

        if (\Cache::has($cache_key)) {
            $filePath = \Cache::get($cache_key);
            $isFileHas = true;
        }

        $processKey = $cache->generateProcessExportUsersKey($group->id);

        $isProcess = false;

        if (\Cache::has($processKey)) {
            $isProcess = \Cache::get($processKey);
        }

        return response()->json([
            'file_path' => $filePath,
            "is_has_file" => $isFileHas,
            "is_process" => $isProcess,
            'key' => $cache_key
        ]);
    }

    public function userStatsChangeNotification(Request $request)
    {
        return $this->resourceClass->changeNotification($request->user()->id, $request->user()->currentAppGroup()->id, $request->all());
    }

    public function getPackageDetails($version, $companyId)
    {
        return $this->resourceClass->getPackageDetails($companyId);
    }

    public function getCompanySideFilters(Request $request)
    {
        return $this->resourceClass->getCompanySideFilters();
    }

    public function getPackageListing($version, $companyId)
    {
        return $this->resourceClass->getPackageListing($version, $companyId);
    }

    public function changePackage(Request $request)
    {
        return $this->resourceClass->changePackage($request->all());
    }
}
