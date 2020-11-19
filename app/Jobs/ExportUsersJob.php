<?php

namespace App\Jobs;

use App\AppUsers;
use App\Cache\CacheKeys;
use App\Notifications\ExportUsersNotification;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Excel;

class ExportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $companyId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $company = User::find($this->companyId);
            $group = $company->currentAppGroup();

            $ext = "xlsx";
            $fileName = 'export_users_app_group_id_' . $group->id;

            Excel::create($fileName, function ($excel) use ($group) {
                $data = array();

                $appUsers = AppUsers::with('company', 'group')
                    ->where('app_group_id', $group->id)->chunk(100, function ($users) use (&$data) {
                        foreach ($users as $user) {
                            array_push($data, [
                                'company' => $user->company ? $user->company->name : '',
                                'group' => $user->group ? $user->group->name : '',
                                'user_id' => $user->user_id,
                                'app_id' => $user->app_id,
                                'username' => $user->username,
                                'firstname' => $user->firstname,
                                'lastname' => $user->lastname,
                                'email' => $user->email,
                                'timezone' => $user->timezone,
                                'latitude' => $user->latitude,
                                'longitude' => $user->latitude,
                                'country' => $user->country,
                                'last_login' => $user->last_login,
                                'enabled' => $user->enabled,
                                'created_at' => $user->created_at,
                                'updated_at' => $user->updated_at
                            ]);
                        }
                    });

                $excel->sheet('users', function ($sheet) use ($data) {
                    $sheet->fromArray($data);
                });
            })->store($ext, storage_path('app/public/exports'));

            $cache = new CacheKeys();
            $cache_key = $cache->generateExportUsersKey($group->id);

            \Cache::forget($cache_key);

            $filePath = url('/download/exportCSVFromEmail/' . $fileName . '.' . $ext);

            \Cache::put($cache_key, $filePath, now()->addDays(10));

            $company->notify(new ExportUsersNotification($filePath));

            $processKey = $cache->generateProcessExportUsersKey($group->id);
            \Cache::forget($processKey);
        } catch (\Exception $exception) {
            \Log::info('Export issue');
            \Log::info($exception->getMessage());
        }
    }
}
