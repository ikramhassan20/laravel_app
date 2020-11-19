<?php

namespace App\Http\Resources\V1\Stats;

use App\AppUserActivity;
use App\AppUsers;
use App\Cache\AppUserLoginSignupCache;
use App\Cache\CampaignConversionStatsCache;
use App\Cache\CampaignStatsCache;
use App\Cache\NewsfeedStatsCache;
use App\Cache\PopularAppsCache;
use App\Campaign;
use App\CampaignTracking;
use App\LinkTrackings;
use App\NewsFeedImpression;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsSummary
{
    private function getCampaign(array $attr = null)
    {
        $user = \Request::user();

        $campaign = CampaignTracking::with('campaign');
        if (isset($attr['type'])) {
            $campaign = $campaign->whereHas('campaign', function ($q) use ($attr) {
                $q->where('campaign_type', $attr['type']);
            });
        }

        if ($user->is_admin == 0) {
            $campaign = $campaign->whereHas('campaign', function ($q) use ($user) {
                $q->where("app_group_id", "=", $user->currentAppGroup()->id);
            });
        }

        if (isset($attr['status'])) {
            $campaign = $campaign->whereIn('status', $attr['status']);
        }

        $campaign = $campaign->count();

        return $campaign;
    }

    /*private function getUsers()
    {
        $user = \Request::user();

        if ($user->is_admin == 0) {
            $appGroupId = \Request::user()->currentAppGroup()->id;

            return AppUsers::where('app_group_id', $appGroupId)->count();
        }

        $users = AppUsers::count();

        return $users;
    }*/

    private function getNewsfeedClicks(array $attr = null)
    {
        $clicks = LinkTrackings::with('newsfeed')->where("rec_type", "newsfeed");

        if (isset($attr['device_type'])) {
            $clicks = $clicks->where("device_type", $attr['device_type']);
        }

        $user = \Request::user();
        if ($user->is_admin == 0) {
            $appGroupId = \Request::user()->currentAppGroup()->id;

            $clicks = $clicks->whereHas('newsfeed', function ($q) use ($appGroupId) {
                $q->where('app_group_id', $appGroupId);
            });
        }

        $clicks = $clicks->count();

        return $clicks;
    }

    private function getNewsFeedViews(array $attr = null)
    {
        $user = \Request::user();
        if (empty($attr)) {
            return NewsFeedImpression::where('user_id', '=', $user->id)->count();
        }


        $views = NewsFeedImpression::with('newsFeed')
            ->where('platform', $attr['platform'])->where('user_id', '=', $user->id);
        if ($user->is_admin == 0) {
            $appGroupId = \Request::user()->currentAppGroup()->id;

            $views = $views->whereHas('newsFeed', function ($q) use ($appGroupId) {
                $q->where('app_group_id', $appGroupId);
            });
        }

        $views = $views->count();

        return $views;
    }

    public function summary(Request $request)
    {
        $users = $this->getUsers();

        $response = [
            'users' => $users,
            'campaigns' => [
                'sent' => [
                    'total' => $this->getCampaign(['status' => ['completed']]),
                    'email' => $this->getCampaign(['status' => ['completed'], 'type' => 'email']),
                    'push' => $this->getCampaign(['status' => ['completed'], 'type' => 'push']),
                    'inapp' => $this->getCampaign(['status' => ['completed'], 'type' => 'inapp'])
                ],
                'failed' => [
                    'total' => $this->getCampaign(['status' => ['failed']]),
                    'email' => $this->getCampaign(['status' => ['failed'], 'type' => 'email']),
                    'push' => $this->getCampaign(['status' => ['failed'], 'type' => 'push']),
                    'inapp' => $this->getCampaign(['status' => ['failed'], 'type' => 'inapp'])
                ],
                'queued' => [
                    'total' => $this->getCampaign(['status' => ['added', 'executing']]),
                    'email' => $this->getCampaign(['status' => ['added', 'executing'], 'type' => 'email']),
                    'push' => $this->getCampaign(['status' => ['added', 'executing'], 'type' => 'push']),
                    'inapp' => $this->getCampaign(['status' => ['added', 'executing'], 'type' => 'inapp'])
                ],
            ],
            'newsfeed' => [
                'clicks' => [
                    'total' => $this->getNewsfeedClicks(),
                    'ios' => $this->getNewsfeedClicks(['device_type' => 'ios']),
                    'android' => $this->getNewsfeedClicks(['device_type' => 'android']),
                    'web' => $this->getNewsfeedClicks(['device_type' => 'web'])
                ],
                'views' => [
                    'total' => $this->getNewsFeedViews(),
                    'ios' => $this->getNewsFeedViews(['platform' => 'ios']),
                    'android' => $this->getNewsFeedViews(['platform' => 'android']),
                    'web' => $this->getNewsFeedViews(['platform' => 'web'])
                ]
            ]
        ];
        return $response;
    }

    public function process(Request $request)
    {
        $response = [
            'users' => 10,
            'campaigns' => [
                'sent' => [
                    'total' => 10,
                    'email' => 5,
                    'push' => 3,
                    'inapp' => 2
                ],
                'failed' => [
                    'total' => 10,
                    'email' => 5,
                    'push' => 3,
                    'inapp' => 2
                ],
                'queued' => [
                    'total' => 10,
                    'email' => 5,
                    'push' => 3,
                    'inapp' => 2
                ],
            ]
        ];

        return $response;
    }

    public function emailCampaign(Request $request)
    {
        $type = $request->get('type');
        $statuses = $this->getStatues();

        if ($type == "weekly") {
            $response = collect();
            $dates = $this->getLastSevenDays();

            foreach ($statuses as $status) {
                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $status['label'],
                    "color" => $status['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($dates as $date) {
                    $count = $this->getCampaignsByDates([
                        'status' => $status['status'],
                        'date' => $date
                    ]);
                    $options->push([
                        "y" => $count, "label" => $date
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            return $response;
        }

        if ($type == "monthly") {
            $months = $this->getMonths();
            $response = collect();

            foreach ($statuses as $status) {
                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $status['label'],
                    "color" => $status['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($months as $month) {
                    $count = $this->getCampaignsByDates([
                        'status' => $status['status'],
                        'month' => $month['m'],
                        'year' => $month['y']
                    ]);
                    $options->push([
                        "y" => $count, "label" => $this->getMonthName($month['m'])
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            return $response;
        }

        if ($type == "yearly") {
            $years = $this->getYears();

            $response = collect();

            foreach ($statuses as $status) {
                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $status['label'],
                    "color" => $status['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($years as $year) {
                    $count = $this->getCampaignsByDates([
                        'status' => $status['status'],
                        'year' => $year
                    ]);
                    $options->push([
                        "y" => $count, "label" => $year
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            return $response;
        }
    }

    private function getStatues()
    {
        $response = [];

        array_push($response, [
            "color" => "#2a8689",
            "label" => "Send",
            "status" => ['completed']
        ]);
        array_push($response, [
            "color" => "#ff2c4d",
            "label" => "Failed",
            "status" => ['failed']
        ]);
        array_push($response, [
            "color" => "#f4d63a",
            "label" => "In Queue",
            "status" => ['added', 'executing']
        ]);

        return $response;
    }

    private function getMonthName($monthNum)
    {
        $dateObj = \DateTime::createFromFormat('!m', $monthNum);
        $monthName = $dateObj->format('F');

        return $monthName;
    }

    private function getCampaignsByDates(array $attr = null)
    {
        $user = \Request::user();

        $campaign = CampaignTracking::with('campaign');

        if (isset($attr['status'])) {
            $campaign = $campaign->whereIn('status', $attr['status']);
        }

        if (isset($attr['date'])) {
            $campaign = $campaign->whereDate('created_at', '=', $attr['date']);
        }

        if (isset($attr['month'])) {
            $campaign = $campaign->whereMonth('created_at', '=', $attr['month']);
        }

        if (isset($attr['year'])) {
            $campaign = $campaign->whereYear('created_at', '=', $attr['year']);
        }

        if ($user->is_admin == 0) {
            $campaign = $campaign->whereHas('campaign', function ($q) use ($user) {
                $q->where("app_group_id", "=", $user->currentAppGroup()->id);
            });
        }

        $campaign = $campaign->count();

        return $campaign;
    }

    private function getWeeklyReport(array $attr = null)
    {
        $sql = "SELECT DATE(created_at) as days, COUNT(*) as record_count, status from campaign_tracking WHERE created_at > DATE_SUB(curdate(), INTERVAL " . $attr['days'] . " day) group by DATE(created_at), status order by DATE(created_at)";
        $records = \DB::select($sql);
        return $records;
        $statuses = $this->getStatues();
        foreach ($statuses as $status) {

        }
    }

    private function getMonths()
    {
        $months = [];

        for ($i = 1; $i <= 12; $i++) {
            array_push($months, [
                'm' => date("m", strtotime(date('Y-m-01') . " -$i months")),
                'y' => date("Y", strtotime(date('Y-m-01') . " -$i months")),
            ]);
        }

        return $months;
    }

    private function getYears()
    {
        $years = [];

        for ($i = 1; $i <= 7; $i++) {
            $years[] = date("Y", strtotime(date('Y-m-01') . " -$i years"));
        }

        return $years;
    }

    private function getLastSevenDays()
    {
        $period = new \DatePeriod(
            new \DateTime(Carbon::now()->addDays(-7)->format('Y-m-d')),
            new \DateInterval('P1D'),
            new \DateTime(Carbon::now()->format('Y-m-d'))
        );
        $dates = [];
        foreach ($period as $key => $value) {
            array_push($dates, $value->format('Y-m-d'));
        }

        return $dates;
    }

    public function getDevices()
    {
        $response = [];

        array_push($response, [
            "color" => "#00a6d0",
            "label" => "IOS",
            "value" => 'IOS'
        ]);
        array_push($response, [
            "color" => "#fd8642",
            "label" => "Android",
            "value" => 'ANDROID'
        ]);
        array_push($response, [
            "color" => "#f4d63a",
            "label" => "Web",
            "value" => 'WEB'
        ]);

        return $response;
    }

    public function conversationCampaign(Request $request)
    {
        $type = $request->get('type');
        $devices = $this->getDevices();
        $response = collect();

        foreach ($devices as $device) {
            if ($type == "weekly") {
                $dates = $this->getLastSevenDays();

                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $device['label'],
                    "color" => $device['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($dates as $date) {
                    $count = $this->getConversations([
                        'device_type' => $device['value'],
                        'date' => $date
                    ]);

                    $options->push([
                        "y" => $count, "label" => $date
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            if ($type == "monthly") {
                $months = $this->getMonths();

                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $device['label'],
                    "color" => $device['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($months as $month) {
                    $count = $this->getConversations([
                        'device_type' => $device['value'],
                        'month' => $month['m'],
                        'year' => $month['y']
                    ]);

                    $options->push([
                        "y" => $count, "label" => $this->getMonthName($month['m'])
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }

            if ($type == "yearly") {
                $years = $this->getYears();

                $data = [
                    "type" => "column",
                    "showInLegend" => true,
                    "name" => $device['label'],
                    "color" => $device['color'],
                    "dataPoints" => []
                ];

                $options = collect();

                foreach ($years as $year) {
                    $count = $this->getConversations([
                        'device_type' => $device['value'],
                        'year' => $year
                    ]);

                    $options->push([
                        "y" => $count, "label" => $year
                    ]);
                }
                $data['dataPoints'] = $options;

                $response->push($data);
            }
        }

        return $response;
    }

    private function getConversations(array $attr = null)
    {
        $user = \Request::user();

        $conversations = AppUserActivity::with('campaign')->where("rec_type", "conversion");

        if (isset($attr['device_type'])) {
            $conversations = $conversations->where('device_type', $attr['device_type']);
        }

        if (isset($attr['date'])) {
            $conversations = $conversations->whereDate('created_at', '=', $attr['date']);
        }

        if (isset($attr['month'])) {
            $conversations = $conversations->whereMonth('created_at', '=', $attr['month']);
        }

        if (isset($attr['year'])) {
            $conversations = $conversations->whereYear('created_at', '=', $attr['year']);
        }

        if ($user->is_admin == 0) {
            $conversations = $conversations->whereHas('campaign', function ($q) use ($user) {
                $q->where("app_group_id", "=", $user->currentAppGroup()->id);
            });
        }

        $conversations = $conversations->count();

        return $conversations;
    }

    public function getUsers($type, $deviceType)
    {
        $subQueryLatestDate = DB::table('app_user_token')
            ->groupBy('row_id')
            ->select('row_id', DB::raw('max(updated_at) as latest_token'));

        $subQueryFullData = DB::table('app_user as au')
            ->join(DB::raw('(' . $subQueryLatestDate->toSql() . ') as aut'), 'au.row_id', '=', 'aut.row_id')
            ->join("app_user_token as aut1", function ($join) {
                $join->on('aut1.row_id', '=', 'aut.row_id');
                $join->on('aut1.updated_at', '=', 'aut.latest_token');
            })
            ->where('au.company_id', Auth::user()->id);

        if (strtolower($deviceType) != null && strtolower($deviceType) != "all") {
            $subQueryFullData->where("aut1.device_type", $deviceType);
        }

        if ($type == "map") {

            $limit = config('engagement.api.limit.dashboard_map_limit');
            if(empty($limit)){
                $limit = 300;
            }

            $chunk_size = config('engagement.api.dashboard_map.chunk_size');
            if(empty($chunk_size)){
                $chunk_size = 100;
            }

            $return = [];
            $subQueryFullData->select(
                DB::raw("CONCAT(au.email, '(', au.user_id, ')-', aut1.app_name) as indentifier"),
                'au.latitude',
                'au.longitude',
                'aut1.device_type'
            )->take($limit)->orderBy('aut1.device_type')->chunk($chunk_size, function ($data_rows) use(&$return) {
                foreach ($data_rows as $row) {
                    $return[] = $row;
                }
            });

            return $return;

        } else {
            return $subQueryFullData->select(
                DB::raw("CONCAT(au.email, '(', au.user_id, ')-', aut1.app_name) as indentifier"),
                'au.row_id',
                'au.latitude',
                'au.longitude',
                'au.username',
                'au.image_url',
                'aut1.device_type'
            )->take(5)->get();
        }

    }

    public function getCampaignStatsCount($request)
    {
        $data = $request->all();

        $campaignStats = [
            "In-Queue" => 0,
            "Fail" => 0,
            "Sent" => 0
        ];

        $campaignStatus = [
            "added" => "In-Queue",
            "failed" => "Fail",
            "completed" => "Sent"
        ];

        $queryChaining = DB::table('campaign')
            ->join('campaign_tracking', 'campaign.id', '=', 'campaign_tracking.campaign_id')
            ->where('campaign.app_group_id', '=', \Request::user()->currentAppGroup()->id)
            ->whereDate("campaign_tracking.created_at", ">=", $data["startDate"])
            ->whereDate("campaign_tracking.created_at", "<=", $data["endDate"]);

        if ($data["selectedType"]["code"] != "all") {
            $queryChaining->where("campaign.campaign_type", $data["selectedType"]["code"]);
        }

        $queryData = $queryChaining->groupBy("campaign_tracking.status")
            ->select("campaign_tracking.status", DB::raw('count(*) as total_rows'))
            ->get();

        foreach ($queryData as $obj) {
            if (isset($campaignStatus[$obj->status])) {
                $campaignStats[$campaignStatus[$obj->status]] = $obj->total_rows;
            }
        }

        return $campaignStats;
    }

    public function getConversionCount($request)
    {
        $data = $request->all();

        $queryChaining = DB::table('campaign')
            ->join('app_user_activity', 'campaign.id', '=', 'app_user_activity.campaign_id')
            ->where('campaign.status', '=', 'active')
            ->where('app_user_activity.rec_type', '=', 'conversion')
            ->where('campaign.app_group_id', '=', \Request::user()->currentAppGroup()->id)
            ->whereDate("app_user_activity.created_at", ">=", $data["startDate"])
            ->whereDate("app_user_activity.created_at", "<=", $data["endDate"]);

        if ($data["selectedType"]["code"] != "all") {
            $queryChaining->where("campaign.campaign_type", $data["selectedType"]["code"]);
        }

        return $queryChaining->count();
    }

    public function getNewsFeedCount($request)
    {
        $data = $request->all();

        $newsFeedStats = [
            "clicks" => 0,
            "views" => 0,
            "clicksInterval" => [],
            "viewsInterval" => []
        ];

        $queryChaining1 = DB::table('news_feed')
            ->join('link_tracking', 'news_feed.id', '=', 'link_tracking.rec_id')
            ->where('link_tracking.rec_type', '=', 'newsfeed')
            ->where('news_feed.status', '=', 'active')
            ->where('news_feed.app_group_id', '=', \Request::user()->currentAppGroup()->id)
            ->where('news_feed.deleted_at', '=', NULL)
            ->whereDate("link_tracking.created_at", ">=", $data["startDate"])
            ->whereDate("link_tracking.created_at", "<=", $data["endDate"]);

        $queryChaining2 = clone $queryChaining1;

        $newsFeedStats["clicks"] = $queryChaining1->count();

        $clicksInterval = $queryChaining2->groupBy(DB::raw("DATE_FORMAT(link_tracking.created_at, '%Y-%m')"))
            ->select(DB::raw("DATE_FORMAT(link_tracking.created_at, '%Y-%m') as date"), DB::raw("count(*) as total_clicks"))
            ->get();

        foreach ($clicksInterval as $click) {
            $newsFeedStats["clicksInterval"][$click->date] = $click->total_clicks;
        }


        $queryChaining1 = DB::table('news_feed')
            ->join('news_feed_impression', 'news_feed.id', '=', 'news_feed_impression.news_feed_id')
            ->where('news_feed.status', '=', 'active')
            ->where('news_feed.app_group_id', '=', \Request::user()->currentAppGroup()->id)
            ->where('news_feed.deleted_at', '=', NULL)
            ->where('news_feed_impression.deleted_at', '=', NULL)
            ->whereDate("news_feed_impression.created_at", ">=", $data["startDate"])
            ->whereDate("news_feed_impression.created_at", "<=", $data["endDate"]);

        $queryChaining2 = clone $queryChaining1;

        $newsFeedStats["views"] = $queryChaining1->count();

        $viewsInterval = $queryChaining2->groupBy(DB::raw("DATE_FORMAT(news_feed_impression.created_at, '%Y-%m')"))
            ->select(DB::raw("DATE_FORMAT(news_feed_impression.created_at, '%Y-%m') as date"), DB::raw("count(*) as total_clicks"))
            ->get();

        foreach ($viewsInterval as $click) {
            $newsFeedStats["viewsInterval"][$click->date] = $click->total_clicks;
        }

        return $newsFeedStats;
    }

    public function getUserStatsCount($request)
    {
        $data = $request->all();

        $queryChain = DB::table("app_user")
            ->where("app_group_id", \Request::user()->currentAppGroup()->id)
            ->where('app_user.is_deleted', 0);

        if ($data["startDate"] != null && $data["startDate"] != "") {
            $queryChain->whereDate("created_at", ">=", $data["startDate"]);
        }

        if ($data["endDate"] != null && $data["endDate"] != "") {
            $queryChain->whereDate("created_at", "<=", $data["endDate"]);
        }

        $queryChainActive = clone $queryChain;
        $queryChainInActive = clone $queryChain;

        $activeUsers = $queryChainActive->where("enabled", 1)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as created_at'), DB::raw('count(*) as totalUsers'))
            ->get();

        $inActiveUsers = $queryChainInActive->where("enabled", 0)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as created_at'), DB::raw('count(*) as totalUsers'))
            ->get();

        return [
            "active" => $activeUsers,
            "inactive" => $inActiveUsers
        ];
    }

    public function getRecentApps($request)
    {
        /*$data = PopularAppsCache::getPopularAppsFromCache($request->user()->currentAppGroup()->id);
        return $data == null ? [] : \GuzzleHttp\json_decode($data, true);*/

        $popular_apps = DB::table('campaign_tracking')
            ->join('app_user_token', 'campaign_tracking.app_user_token_id', '=', 'app_user_token.id')
            ->join('app', 'app.app_id', '=', 'app_user_token.app_id')
            ->where('campaign_tracking.status', '=', 'completed')
            ->where('app.app_group_id', '=', $request->user()->currentAppGroup()->id)
            ->groupBy('app.app_id', 'app.name', 'app.logo', 'app.app_group_id', 'app.id')
            ->orderBy('total_send', 'desc')
            ->limit(5)
            ->select(DB::raw('count(*) as total_send'), 'app.app_id','app.name','app.logo','app.app_group_id','app.id')
            ->get();

        return $popular_apps;
    }
}
