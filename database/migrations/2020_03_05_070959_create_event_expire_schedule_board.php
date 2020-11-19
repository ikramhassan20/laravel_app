<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class Create_event_expire_schedule_board
 * @author  Ikram Hassan
 */
class CreateEventExpireScheduleBoard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config_database_definer = env('DB_USERNAME');
        $eventName = \App\Components\SQL_SP_VW_Identifier::EVENT_EXPIRE_SCHEDULE_BOARD;

        Db::connection()->getPdo()->exec("CREATE DEFINER=`$config_database_definer`@`%` EVENT `$eventName`
        ON SCHEDULE
            EVERY 30 SECOND STARTS '2020-03-05 12:37:18'
        ON COMPLETION NOT PRESERVE         
        ENABLE
        COMMENT ''
        DO BEGIN

#mark board expire
UPDATE board    SET status='expired'   WHERE status='active'  AND   UTC_TIMESTAMP() > end_time AND (end_time IS NOT NULL AND end_time <> '0000-00-00 00:00:00'); 

# expire scheduled board with schedule only once 
UPDATE board b INNER JOIN board_queue bq ON bq.board_id=b.id
SET b.status='expired'
WHERE LOWER(b.schedule_type) = 'once' 
AND b.delivery_type='schedule' 
AND b.status='active'
AND LOWER(bq.`status`)='complete';
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