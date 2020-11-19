<?php

namespace App\Components;

class AppStatusMessages
{
    const CODE_ALREADY_EXIST = 'Code already exist.';
    const SUCCESS = 'success';
    const update = 'Record updated successfully.';
    const DUPLICATE_ENTRY = 'Duplicate record.';
    const CANNOT_CREATE_RECORD = 'Cannot create record.';
    const IN_VALID_PLATFORM = 'For push notifications, a valid platform must be provided.';
    const NO_NOTIFICATIONS_SENT = 'No notifications were sent as compiled data for campaign was invalid.';
//////////////////////////// Campaign Types /////////////////////
    const CAMPAIGN_TYPE_PUSH = 'CAMPAIGN_TYPE_PUSH';
    const CAMPAIGN_TYPE_IN_APP = 'CAMPAIGN_TYPE_IN_APP';
    const CAMPAIGN_TYPE_EMAIL = 'CAMPAIGN_TYPE_EMAIL';
    const PLATFORM_TYPE_ANDROID = 'Android';
    const PLATFORM_TYPE_IOS = 'Ios';
    const PLATFORM_TYPE_UNIVERSAL = 'Universal';
    ////////campaign status
    const CAMPAIGN_STATUS_DRAFT = 'draft';
    const CAMPAIGN_STATUS_ACTIVE = 'active';
    const CAMPAIGN_STATUS_SUSPENDED = 'suspended';
    const CAMPAIGN_STATUS_EXPIRED = 'expired';

    ////////campaign Delivery Types
    const CAMPAIGN_DELIVERY_TYPE_SCHEDULE = 'schedule';
    const CAMPAIGN_DELIVERY_TYPE_ACTION = 'action';
    const CAMPAIGN_DELIVERY_TYPE_api = 'api';


    /////////////////////////Commands Messages//////////////////////////
    const UNABLE_TO_ADD_ITEMS_QUEUE = 'Unable to add items to campaign queues, no valid campaign records found.';
    const ADD_ITEMS_QUEUE_SUCCESS = 'Items added into campaign queues successfully.';
    const STATUS_CODE_NOT_FOUND = 'Status code not found.';

}