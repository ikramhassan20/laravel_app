<?php

namespace App\Http\Controllers\Api;

use App\Attribute;
use App\AttributeData;
use App\ImportData;
use App\Jobs\ImportDataJob;
use App\Traits\CommonTrait;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Components\AppStatusCodes;
use Illuminate\Support\Facades\Log;

class ImportDataController extends Controller
{
    use CommonTrait;

    private $groupId, $company;

    public function __construct()
    {
        $this->setResourceClass('import_data');
    }

    public function downloadSampleFile()
    {
        $pathToFile = storage_path('sample_att_data_file/sample-attribute-data-file.xlsx');

        return response()->download($pathToFile);
    }

    public function downloadExportCSV($companyID, $filename)
    {
        $pathToFile = storage_path('app/public/company_' . $companyID . '/export/' . $filename . '.csv');

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        return response()->download($pathToFile, 'exportData.csv', $headers);
    }

    public function exportAppUsersFileFromEmail($filename)
    {
        $pathToFile = storage_path('app/public/exports/' . $filename);

        return response()->download($pathToFile, $filename);
    }

    public function index(Request $request)
    {
        return $this->resourceClass->all($request);
    }

    public function importTargetedUsers(Request $request)
    {

        $company = Auth::user();
        $this->groupId = $company->currentAppGroup()->id;
        $this->company = $company;

        //ini_set('memory_limit', '-1');
        //ini_set('max_execution_time', '-1');
//        $pattern = preg_quote('#$%^&* ()+=-[]\';,./{}|\":<>?~', '#');

        try {
            $companyId = $this->company->id;
            $importDataId = $request->id;
            $importData = ImportData::where('id', $importDataId)->where('company_id', '=', $companyId)->first();

            if (empty($importData)) {
                Log::error("Import ID not found. ");
                return $this->addResponse(
                    AppStatusCodes::HTTP_NOT_FOUND,
                    'error',
                    ['Import ID not found.'],
                    'error'
                );
            }


            self::saveFileToLocal($importData, $company);

            $filesCount = 0;

            $filePath = str_replace(public_path(), '', $importData->file_path);

            if ($filePath) {

                // preparing sheet path
                $sheet_load_from = storage_path('app') . '/public/company_' . $this->company->id . '/' . $importData->file_name;
                Log::info("Import Data sheet load from: " . $sheet_load_from);

                // loading excel sheet
                $sheets = Excel::load($sheet_load_from)->get();

//                $sheets = Excel::load(storage_path('sample_att_data_file/sample-attribute-data-file.xlsx'))->get();

                foreach ($sheets as $sheet) {
                    if ($sheet->getTitle() == 'standard') {
                        Log::info("Import Data standard sheet processing: " . count($sheet));
                        foreach ($sheet as $item) {
                            $itemIterator = $item->getIterator()->getArrayCopy();
                            $this->saveAttribute($itemIterator, 'user');
                        }
                    } elseif ($sheet->getTitle() == 'user') {
                        $chunkedData = $sheet->chunk(config('engagement.api.bulk_import.chunk_size')); //    env("IMPORT_DATA_CHUNK_SIZE", 1000)
                        $directory = 'company_' . $companyId . '/' . 'attribute_file_' . $importDataId;
                        Log::info("Import Data user sheet from: " . $directory);

                        $disk = Storage::disk('s3');
                        if (!$disk->exists($directory)) {
                            $disk->makeDirectory($directory);
                        } else {
                            $disk->deleteDirectory($directory);
                        }

                        foreach ($chunkedData as $item) {
                            $filesCount++;
                            $excelAttributeDataRow = $item->getIterator()->getArrayCopy();
                            $excelAttributeDataRowJson = json_encode($excelAttributeDataRow);
                            $fileName = time() . "_company_data_import_" . $companyId . rand(1, 12000) . '.json';

                            $disk->put($directory . '/' . $fileName, $excelAttributeDataRowJson, 'public');

                            $new_job = array(
                                'job_interval' => "",
                                'job_file_name' => $fileName,
                                'import_data_id' => $importDataId,
                                'company_id' => $companyId
                            );
                            // dd($new_job);
//
//                            ImportDataJob::dispatch($new_job)
//                                ->onQueue('import')
//                                ->delay(now()->addSeconds(5));
                            Log::info("Import Data creating job, file: " . $new_job['job_file_name'] . " :data id: " . $importDataId);
                            \Queue::pushOn('import',
                                new ImportDataJob($new_job)
                            );
                        }
                    } elseif ($sheet->getTitle() == 'conversion') {
                        Log::info("Import Data conversion sheet: " . count($sheet));
                        $this->conversionAndAction($sheet, 'conversion');
                    } elseif ($sheet->getTitle() == 'action') {
                        Log::info("Import Data action sheet: " . count($sheet));
                        $this->conversionAndAction($sheet, 'action');
                    } else {
                        continue;
                    }
                }
            }

            \DB::table('import_data')->where(['id' => $importDataId])->update([
                'status' => 'Inprogress',
                'process_date' => Carbon::now(),
                'remaining_files' => $filesCount
            ]);
            Log::info("Import file added to queues.");

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                ['Import file added to queues.'],
                'success'
            );
        } catch (\Exception $exp) {
            Log::error("Import Data Error: " . $exp->getMessage());
            return $this->addResponse(
                AppStatusCodes::HTTP_NOT_FOUND,
                'error',
                [$exp->getMessage()],
                'error'
            );
        }
    }

    private function saveAttribute($item, $type)
    {
        $groupId = $this->groupId;
        $attribute = Attribute::where('app_group_id', $groupId)->where('code', $item['code'])->first();
        if (empty($attribute)) {
            $attribute = new Attribute();
        }

        $attribute->app_group_id = $groupId;
        $attribute->code = $item['code'];
        $attribute->name = $item['code'];
        $attribute->alias = $item['code'];
        $attribute->level_type = 'custom';
        $attribute->data_type = $item['data_type'];
        $attribute->attribute_type = $type;
        $attribute->length = $item['length'];
        $attribute->source_table_name = '';
        $attribute->value_column = '';
        $attribute->text_column = '';
        $attribute->where_condition = '';

        $attribute->save();

        return $attribute;
    }

    private function conversionAndAction($sheet, $type)
    {

        foreach ($sheet as $item) {
            $itemIterator = $item->getIterator()->getArrayCopy();
            if (!empty($item['key']) && !empty($itemIterator['data_type']) && !empty($itemIterator['length'])) {
                $attribute = $this->saveAttribute(["code" => $itemIterator['key'], "data_type" => $itemIterator['data_type'], "length" => $itemIterator['length']], $type);
                $this->saveAttributeData($attribute, $itemIterator);
            }
        }
    }

    private function saveAttributeData($attribute, $itemIterator)
    {
        $company = $this->company;
        $attributeData = AttributeData::where('company_id', $company->id)->where('code', $attribute->code)->first();
        if (empty($attributeData)) {
            $attributeData = new AttributeData();
        }

        $attributeData->company_id = $company->id;
        $attributeData->row_id = self::generateRowId($company->id);
        $attributeData->code = $attribute->code;
        $attributeData->value = (!empty($itemIterator['value'])) ? $itemIterator['value'] : '';
        $attributeData->data_type = $attribute->attribute_type;
        $attributeData->created_by = $company->id;

        $attributeData->save();
    }

    public function deleteImportFile(Request $request)
    {
        try {
            $companyID = $request->user()->id;
            $importData = ImportData::find($request->get('id'));

            if ($importData['company_id'] != $companyID) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'error',
                    ['Invalid user.'],
                    'error'
                );
            }

            if (empty($importData)) {
                return $this->addResponse(
                    AppStatusCodes::HTTP_NOT_FOUND,
                    'error',
                    ['Import data not found.'],
                    'error'
                );
            }

            $filePath = $importData->file_path;

            \DB::beginTransaction();

            $importData->delete();

            Storage::delete($importData->file_name);

            \DB::commit();

            return response()->json('Import file deleted successfully.');
        } catch (\Exception $exception) {
            \DB::rollBack();
            return response()->json($exception->getMessage(), 401);
        }
    }
}
