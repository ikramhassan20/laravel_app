<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class Create_event_expire_schedule_campaign
 * @author  Ikram Hassan
 */
class CreateEventExpireScheduleCampaign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config_database_definer = env('DB_USERNAME');
        $eventName = \App\Components\SQL_SP_VW_Identifier::EVENT_EXPIRE_SCHEDULE_CAMPAIGN;

        Db::connection()->getPdo()->exec("CREATE DEFINER=`$config_database_definer`@`%` EVENT `$eventName`
        ON SCHEDULE
            EVERY 30 SECOND STARTS '2020-03-05 12:37:18'
        ON COMPLETION NOT PRESERVE         
        ENABLE
        COMMENT ''
        DO BEGIN

#mark campaign expire
UPDATE campaign 
SET 
    STATUS = 'expired'
WHERE
    STATUS = 'active'
        AND UTC_TIMESTAMP() > end_time
        AND end_time IS NOT NULL
        AND id NOT IN (SELECT 
            campaign_id
        FROM
            hermes_v3.campaign_tracking
        WHERE
            status IN ('added' , 'executing')
        GROUP BY campaign_id
        HAVING COUNT(*) > 10);




#mark newsfeed expire
UPDATE news_feed set status='expired'  WHERE status='active'  AND   UTC_TIMESTAMP() > end_time AND (end_time IS NOT NULL AND end_time <> '0000-00-00 00:00:00'); 

#4 Update and expire scheduled campaign with schedule only once 
UPDATE campaign c
        INNER JOIN
    campaign_queue cq ON cq.campaign_id = c.id 
SET 
    c.status = 'expired'
WHERE
    LOWER(c.schedule_type) = 'once'
        AND c.delivery_type = 'schedule'
        AND c.status = 'active'
        AND LOWER(cq.`status`) = 'complete'
        AND c.id NOT IN (SELECT 
            campaign_id
        FROM
            hermes_v3.campaign_tracking
        WHERE
            status IN ('added' , 'executing')
        GROUP BY campaign_id
        HAVING COUNT(*) > 10);
END" );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /**
         * @author  Ikram Hassan
         */
        ///////// drop sp no need to implement
    }
}