<?php

namespace App\Jobs;

use App\AppUsers;
use App\Events\AppUserSignupCacheEvent;
use App\Cache\AppUserLoginSignupCache;
use App\Helpers\CommonHelper;
use App\Http\Resources\V1\AttributeResource;
use App\Http\Resources\V1\Users\App\SyncUserData;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $params = array();
    public $_job_data = [];
    public $job_file_name = "";
    public $company_id = "";
    public $import_data_id = "";
    public $log;
    private $_job_interval = "";

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->_job_data = $params;
        $this->company_id = $params["company_id"];
        $this->job_file_name = $params["job_file_name"];
        $this->import_data_id = $params["import_data_id"];
        $this->log = [];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Received Import Data Job# : ' . $this->job->getJobId());
        \Log::info('company_' . $this->company_id . '/' . $this->job_file_name);

        $disk = \Storage::disk("s3");
        $directory = 'company_' . $this->company_id . '/' . 'attribute_file_' . $this->import_data_id;
        $company = User::find($this->company_id);

        //\Log::info('companylist' . $company);
        //$groupId = $company->currentAppGroup()->id;

        try {
            $now = Carbon::now();
            $items = \GuzzleHttp\json_decode(
                $disk->get($directory . '/' . $this->job_file_name),
                true
            );

            foreach ($items as $itemKey => $item) {
                $columns = array();

                \Log::info('item called: ' . $itemKey);

                /* in case of date time object */
                foreach ($item as $key => $value) {
                    if ($key == 'last_login') {
                        $item[$key] = date('Y-m-d H:i:s');
                    } elseif (is_array($value) && isset($value['date'])) {
                        $item[$key] = $value['date'];
                    }
                    if ($key !== 0) {
                        array_push($columns, $key);
                    }
                }

                $item['app_name'] = trim($item['app_name']);
                $item['app_id'] = trim($item['app_id']);
                $item['device_type'] = trim($item['device_type']);
                //   \Log::info('items' . var_dump($item));
                $validator = Validator::make($item, [
                    'app_id' => 'required',
                    'user_id' => 'required',
                    'email' => 'required',
                    'device_type' => 'required',
                    'app_name' => 'required'
                ]);
                if (empty($validator->errors()->all())) {
                    $item['is_import'] = 1;
                    if (!isset($item['is_active'])) {
                        $item['is_active'] = 1;
                    }
                    try {
                        DB::beginTransaction();
                        $groupId = CommonHelper::getAppGroupIdForImport($item['app_id'], $item['app_name'], $item['device_type'], $this->company_id);
                        if ($groupId != "") {
                            \Log::info('group id: ' . $groupId);
                            $item['company_id'] = $this->company_id;
                            $item['mode'] = AppUsers::USER_IMPORT;
                            $item['app_group_id'] = $groupId;

                            $headers = [
                                "app-id" => [$item['app_id']],
                                "app-name" => [$item['app_name']],
                                "device-type" => [$item['device_type']]
                            ];

                            $userData = new SyncUserData($this->company_id);
                            $userData->itemKey($item);
                            $userData->save($item);

                            $attributeObj = new AttributeResource();
                            $attributeObj->updateAttributeData($columns, $item, $company, $headers);

                            //event(new AppUserSignupCacheEvent($item));
                            $login_cache = new AppUserLoginSignupCache();
                            $response = $login_cache->saveAppUserSignupCache($item);

                            $fileCount = count($disk->allFiles($directory));
                            DB::table('import_data')->where(['id' => $this->import_data_id])->update([
                                'remaining_files' => $fileCount
                            ]);
                            \Log::info('successed, filecount:' . $fileCount);

                        }
                        DB::commit();
                    } catch (\Exception $exception) {
                        DB::rollBack();
                        \Log::info(['LogInfo' => $exception->getMessage() . ' Line #: ' . $exception->getLine()]);
                        $this->log[] = array(
                            'FunctionName' => 'Exception in ImportDataJob.php',
                            'LogInfo' => $exception->getMessage() . ' Line #: ' . $exception->getLine(),
                        );
                        $fileCount = count($disk->allFiles($directory));
                        if ($fileCount == 1) {
                            DB::table('import_data')->where(['id' => $this->import_data_id])->update([
                                'status' => 'Failed',
                                'process_date' => Carbon::now(),
                                'reason' => $exception->getMessage()
                            ]);
                        }
                        \Log::info('exception, filecount:' . $fileCount);
                        return false;
                    }
                }
            }
        } catch (\Exception $exception) {
            \Log::info(['LogInfo' => $exception->getMessage() . ' Line #: ' . $exception->getLine()]);
            $this->log[] = array(
                'FunctionName' => 'Exception in ImportDataJob.php',
                'LogInfo' => $exception->getMessage() . ' Line #: ' . $exception->getLine(),
            );
            $fileCount = count($disk->allFiles($directory));
            if ($fileCount == 1) {
                DB::table('import_data')->where(['id' => $this->import_data_id])->update([
                    'status' => 'Failed',
                    'process_date' => Carbon::now(),
                    'reason' => $exception->getMessage()
                ]);
            }
            \Log::info('exception2, filecount:' . $fileCount);
            return false;
        }

        $disk->delete($directory . '/' . $this->job_file_name);
        $fileCount = count($disk->allFiles($directory));
        DB::table('import_data')->where(['id' => $this->import_data_id])->update([
            'remaining_files' => $fileCount
        ]);
        \Log::info('-----------------------------------------------' . $fileCount);
        if ($fileCount == 0) {
            \Log::info('completed');
            DB::table('import_data')->where(['id' => $this->import_data_id])->update([
                'status' => 'Complete',
                'process_date' => Carbon::now(),
                'remaining_files' => 0
            ]);
            $disk->deleteDirectory($directory);
            /*Artisan::call('attribute:cache', [
                '--company' => $this->company_id
            ]);*/
        }
    }
}
