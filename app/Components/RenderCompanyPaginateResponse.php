<?php

namespace App\Components;

use App\AppUsers;
use App\Language;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\User;

trait RenderCompanyPaginateResponse
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */

    public function RenderCompanyPaginateResponse($model, Request $request)
    {
        $authUser = \Request::user();

        $user = $request->user();

        $group = $user->currentAppGroup();

        $subQueryLocationCount = DB::table("app_user")
            ->select("app_user.company_id", \DB::raw('COUNT(app_user.company_id) as total'))
            ->groupBy("app_user.company_id");

        $queryChain = $model::leftjoin('user_has_roles', 'user_has_roles.user_id', '=', 'users.id')
            ->leftjoin('roles', 'roles.id', '=', 'user_has_roles.role_id')
            ->leftjoin(DB::raw('(' . $subQueryLocationCount->toSql() . ') as totalTable'), 'totalTable.company_id', '=', 'users.id')
            ->leftjoin('user_package_history', 'users.id', '=', 'user_package_history.user_id')
            ->leftjoin('package', 'package.id', '=', 'user_package_history.package_id')
            ->where(function ($query) {
                $query->where("user_package_history.is_active", 1)
                    ->orWhereNull("user_package_history.is_active");
            });


        if ($user->is_admin == 0) {
            $queryChain = $queryChain->where('app_user.app_group_id', '=', $group->id);
        }
        if ($authUser->is_admin == 1) {
            $queryChain = $queryChain->where('roles.name', 'COMPANY');
        }

        $totalCount = clone $queryChain;
        $totalCount = $totalCount->count();
        if ($request['sideFilters'] != null && $request['sideFilters'] != [] && $request['sideFilters'] != "") {

            if ($request['sideFilters']['parent'] == "is_active") {
                $queryChain->where('users.is_active', '=', $request['sideFilters']['value']);
            } else if ($request['sideFilters']['parent'] == "name") {
                $queryChain->where('package.name', '=', $request['sideFilters']['value']);
            }
        }
        if ($request['query'] != null) {
            $search = $request['query'];
            $queryChain->where(function ($query) use ($search) {
                $query->where('users.id', 'LIKE', "%{$search}%");
                $query->orWhere('users.name', 'LIKE', "%{$search}%");
                $query->orWhere('users.email', 'LIKE', "%{$search}%");
                $query->orWhere('users.created_at', 'LIKE', "%{$search}%");
                /*$query->orWhere('roles.name', 'LIKE', "%{$search}%");*/
                $query->orWhere('totalTable.total', 'LIKE', "%{$search}%");
                $query->orWhere('package.name', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = clone $queryChain;
        $totalFiltered = $totalFiltered->count();
        isset($request["orderBy"]) ? $queryChain->orderBy($request["orderBy"], $request["ascending"] == 1 ? 'desc' : 'asc') : $queryChain->orderBy('updated_at', 'desc');
        $data = $queryChain->offset(($request['page'] - 1) * $request['limit'])
            ->limit($request['limit'])
            //->groupBy('users.id', 'roles.id')
            ->get(['users.*'/*, 'roles.name as roleName'*/, 'package.name as packageName', 'totalTable.total AS app_user_count']);
        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalCount,
        ];
        for ($val = 0; $val < count($data); $val++) {
            if ($data[$val]['is_active'] == 1) {
                $data[$val]['is_active'] = 'Active';
                $data[$val]['status'] = 1;
            } else {
                $data[$val]['is_active'] = 'InActive';
                $data[$val]['status'] = 0;
            }
            if ($data[$val]['app_user_count'] == 0) {
                $data[$val]['app_user_count'] = 0;
            }
        }
        return [
            'meta' => $meta,
            'data' => $data
        ];
    }


    public function RenderCompanyUsersPaginateResponse($model, Request $request, $isUnsubscribeList = false)
    {

        $countQuery = $this->prepareUserListingQuery($request, true, $isUnsubscribeList);
        $dataQuery = $this->prepareUserListingQuery($request, false, $isUnsubscribeList);
        $totalFiltered = (DB::select($countQuery))[0]->count;
        $data = DB::select($dataQuery);

        $meta = [
            'pages' => ceil($totalFiltered / $request['limit']),
            'page' => $request['page'],
            'total' => $totalFiltered,
        ];

        return [
            'meta' => $meta,
            'data' => $data
        ];
    }

    public function prepareUserListingQuery($request, $isCount, $isUnsubscribeList)
    {
        $whereClauseForUnsubscribeList = '';
        if ($isUnsubscribeList) {
            $whereClauseForUnsubscribeList = ' AND `email_notification` = 0 ';
        }
        $user = $request->user();
        $group = $user->currentAppGroup();
        $companyId = $request->user()->id;
        if ($isCount) {
            $selectClause = "SELECT count(*) as count";
        } else {
            $selectClause = "SELECT `user_id`,
                `row_id`,
                `firstname`,
                `lastname`,
                `username`,
                `email`,
                `image_url`,
                `status`,
                `updated_at`,
                `last_login`";
        }

        $query = "{$selectClause} 
                
            FROM
                `app_user`
            WHERE
                `company_id` = {$companyId}
                    {$whereClauseForUnsubscribeList}
                    AND `app_user`.`deleted_at` IS NULL";


        if ($user->is_admin == 0) {
            $query .= " AND `app_user`.`app_group_id` = {$group->id}";
        }

        if ($request['sideFilters']) {
            $status = 0;
            if ($request['sideFilters'] == 'Active') {
                $status = 1;
            }
            $query .= " AND `app_user`.`status` = {$status}";
        }

        if ($request['query'] != null || $request['query'] == 0) {
            $search = $request['query'];
            if ($search == "Active") {
                $search = 1;
                $query .= " AND `app_user`.`status` = {$search}";
            } else if ($search == 'InActive') {
                $search = 0;
                $query .= " AND `app_user`.`status` = {$search}";
            } else {
                $query .= " AND (
                    `app_user`.`row_id` LIKE '%{$search}%'
                    OR `app_user`.`user_id` LIKE '%{$search}%'
                    OR `app_user`.`firstname` LIKE '%{$search}%'
                    OR `app_user`.`lastname` LIKE '%{$search}%'
                    OR `app_user`.`username` LIKE '%{$search}%'
                    OR `app_user`.`app_id` LIKE '%{$search}%'
                    OR `app_user`.`email` LIKE '%{$search}%'
                    OR `app_user`.`last_login` LIKE '%{$search}%'
                    )";
            }
        }

        if (!$isCount) {
            if (isset($request["orderBy"])) {
                $orderBy = $request["orderBy"];
                if ($orderBy == "is_active") {
                    $orderBy = "status";
                }
                $order = $request["ascending"] == 1 ? 'desc' : 'asc';
                $query .= " ORDER BY `{$orderBy}` {$order}";
            } else {
                $query .= " ORDER BY `updated_at` DESC";
            }

            $query .= " LIMIT {$request['limit']}";

            $offset = ($request['page'] - 1) * $request['limit'];
            $query .= " OFFSET {$offset}";
        }

        return $query;
    }

    public function RenderCompanyStatusResponse(Request $request)
    {

        if ($request->is_active == 1) {
            $isActive = true;
        } else {
            $isActive = false;
        }
        $user = User::where('id', $request->id)->update([
            'is_active' => $isActive
        ]);
        if ($user) {
            $response = array(
                'status' => '200',
                'message' => 'Record Updated'
            );

        } else {
            $response = array(
                'status' => '400',
                'message' => 'Record Updated'
            );
        }
        return $response;
    }

}
