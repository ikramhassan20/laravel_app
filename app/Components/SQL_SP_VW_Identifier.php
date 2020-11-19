<?php

namespace App\Components;

/**
 * Class SQL_SP_VW_Calls
 * @package App\Components
 * @Documentation This class refers the mysql stored Procedures, View and Schedules
 */
class SQL_SP_VW_Identifier
{
    const VIEW_CAMPAIGN_QUEUES = 'view_campaign_queues';
    const SP_GET_SEGMENT_ROW_ID = 'sp_get_segment_rowid';
    const SP_SPLITTER_COUNT = 'splitter_count';
    const EVENT_EXPIRE_SCHEDULE_CAMPAIGN = 'expire_schedule_campaign';
    const EVENT_EXPIRE_SCHEDULE_BOARD = 'expire_schedule_board';

}