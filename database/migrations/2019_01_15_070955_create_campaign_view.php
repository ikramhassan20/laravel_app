<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCampaignView
 * @author  Omair Afzal
 */
class CreateCampaignView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config_database_name = config('database.connections.' . config('database.default') . '.database');
        $viewName = \App\Components\SQL_SP_VW_Identifier::VIEW_CAMPAIGN_QUEUES;

        Db::connection()->getPdo()->exec("CREATE VIEW $config_database_name.$viewName 
        AS select concat('{\"campaignId\":\"',`_c`.`id`,'\", ','\"scheduleType\":\"',
        `_c`.`schedule_type`,'\", ','\"sendingDate\":\"',
        date_format(utc_timestamp(),'%Y-%m-%d'),'\", 
        ','\"campaignDay\":\"',if(isnull(`_cs`.`campaign_id`),'',`_cs`.`day`),'\"}') AS `details`,
        concat(date_format(utc_timestamp(),'%Y-%m-%d %H:%i:%s') ) AS 
        `start_at`,`_c`.`id` AS `id` from (((`campaign` `_c` left join `campaign_schedule` `_cs` 
        on((`_c`.`id` = `_cs`.`campaign_id`))) join `app_group` `_ag` 
        on((`_ag`.`id` = `_c`.`app_group_id`))) join `users` `_U` on((`_U`.`id` = `_ag`.`company_id`))) 
        where ((`_U`.`is_active` = 1) and (`_c`.`status` = 'active') and (`_c`.`delivery_type` = 'schedule') 
        and ((((`_c`.`schedule_type` = 'DAILY') or ((`_c`.`schedule_type` = 'WEEEKLY') 
        and (`_cs`.`day` = upper(date_format(utc_timestamp(),'%W')) 
        ))) and (`_c`.`end_time` >= utc_timestamp()) and (`_c`.`start_time` <= utc_timestamp()) and 
        (date_format(`_c`.`start_time`,'%H:%i:%s') <= date_format(utc_timestamp(),'%H:%i:%s'))) or 
        ((`_c`.`schedule_type` = 'ONCE') and (cast(`_c`.`start_time` as date) = cast(utc_timestamp() as date)) 
        and (date_format(`_c`.`start_time`,'%H:%i:%s') <= date_format(utc_timestamp(),'%H:%i:%s')))) 
        and (not(concat('{\"campaignId\":\"',`_c`.`id`,'\", ','\"scheduleType\":\"',`_c`.`schedule_type`,'\", 
        ','\"sendingDate\":\"',date_format(utc_timestamp(),'%Y-%m-%d'),'\", 
        ','\"campaignDay\":\"',if(isnull(`_cs`.`campaign_id`),'',`_cs`.`day`),'\"}')IN 
        (SELECT `campaign_queue`.`details` from `campaign_queue`))))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        DB::connection()->getPdo()->exec("DROP VIEW companies ...");
    }
}
