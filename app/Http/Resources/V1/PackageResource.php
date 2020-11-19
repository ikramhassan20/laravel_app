<?php


namespace App\Http\Resources\V1;

use App\Components\RandomString;
use Illuminate\Http\Request;
use App\Components\ParseResponse;
use App\Http\Resources\ResourcesSteps;
use App\Components\AppStatusCodes;
use App\Package;
use Illuminate\Support\Facades\DB;


class PackageResource
{
    use ParseResponse, ResourcesSteps;

    public function insertPackage(Request $request)
    {
        try {
            $mode = "added";
            $data = $request->all();

            $package = (object)[];
            if ($data['id'] == null || $data['id'] == "") {
                $package = new Package();
                $package->code = RandomString::generateWithPrefix('package');
            } else {
                $mode = "updated";
                $package = Package::find($data['id']);
            }

            $package->name = $data['name'];
            $package->description = $data['description'];
            $package->type = $data['type'];
            $package->push_limit = $data['pushLimit'];
            $package->email_limit = $data['emailLimit'];
            $package->inapp_limit = $data['inAppLimit'];
            $package->nfc_limit = $data['nfcLimit'];
            $package->is_active = 1;

            $package->save();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'package ' . $mode . ' successfully',
                [],
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'error',
                $exception->getMessage(),
                'data'
            );
        }
    }

    public function getPackage(Request $request, $packageId)
    {
        try {

            $package = DB::table("package")
                ->leftJoin("user_package_history", "package.id", "=", "user_package_history.package_id")
                ->where("package.id", $packageId)
                ->where(function ($query) {
                    $query->where("user_package_history.is_active", 1)
                        ->orWhereNull("user_package_history.is_active");
                })
                ->select(
                    'package.id',
                    'package.name',
                    'package.description',
                    'package.type',
                    'package.push_limit as pushLimit',
                    'package.email_limit as emailLimit',
                    'package.inapp_limit as inAppLimit',
                    'package.nfc_limit as nfcLimit',
                    'user_package_history.user_id as packageSubscriber'
                )->first();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'package Details',
                $package,
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'error',
                $exception->getMessage(),
                'data'
            );
        }
    }

    public function packageListing(Request $request)
    {
        try {

            $queryChain = DB::table("package");

            $totalCount = clone $queryChain;
            $totalCount = $totalCount->count();

            if ($request['sideFilters'] != null && $request['sideFilters'] != []) {
                $queryChain->where($request['sideFilters']['parent'], $request['sideFilters']['value']);
            }

            if ($request['query'] != null) {
                $search = $request['query'];
                $queryChain->where(function ($query) use ($search) {
                    $query->where("name", 'LIKE', "%{$search}%")
                        ->orWhere("type", 'LIKE', "%{$search}%")
                        ->orWhere("push_limit", 'LIKE', "%{$search}%")
                        ->orWhere("inapp_limit", 'LIKE', "%{$search}%")
                        ->orWhere("email_limit", 'LIKE', "%{$search}%")
                        ->orWhere("nfc_limit", 'LIKE', "%{$search}%");
                });
            }

            $totalFiltered = clone $queryChain;
            $totalFiltered = $totalFiltered->count();

            isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
            $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
                ->limit($request['limit'])
                ->select("id", "name", "type", "push_limit", "inapp_limit", "email_limit", "nfc_limit", 'is_active')
                ->get();

            $meta = [
                'pages' => ceil($totalFiltered / $request['limit']),
                'page' => $request['page'],
                'total' => $totalFiltered,
            ];

            foreach ($data as $record) {
                $record->status = $record->is_active == 1 ? 'Active' : 'Inactive';
            }


            $response = [
                'meta' => $meta,
                'data' => $data
            ];

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                $response['data'],
                'data',
                $response['meta']
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

    public function getAssociateCompanies(Request $request, $packageId)
    {
        try {

            $companies = DB::table("users")
                ->join("user_package_history", "users.id", "=", "user_package_history.user_id")
                ->where("user_package_history.is_active", "=", 1)
                ->where("user_package_history.package_id", $packageId)
                ->select("users.id", "users.name", "users.email", "user_package_history.start_time", "user_package_history.end_time")
                ->get();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'Associated companies',
                $companies,
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'error',
                $exception->getMessage(),
                'data'
            );
        }
    }

    public function changePackageStatus(Request $request, $packageId, $status)
    {
        try {

            $subscriberExist = DB::table("user_package_history")
                ->where("package_id", $packageId)
                ->where("is_active", 1)
                ->first();

            if ($subscriberExist && strtolower($status) == "inactive") {
                return $this->addResponse(
                    AppStatusCodes::HTTP_OK,
                    'error',
                    'Package has subscriber(s) and cannot be inactivated',
                    'data'
                );
            }

            $package = Package::find($packageId);
            $package->is_active = strtolower($status) == "inactive" ? 0 : 1;
            $package->save();

            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'success',
                'Package ' . (strtolower($status) == "inactive" ? 'Inactivated' : 'activated') . ' successfully',
                'data'
            );

        } catch (\Exception $exception) {
            return $this->addResponse(
                AppStatusCodes::HTTP_OK,
                'error',
                $exception->getMessage(),
                'data'
            );
        }
    }
}