<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Concerns\Http\Resources\AppUserCacheItem;
use App\Http\Resources\Contracts\ProcessItemDataContract;

class SyncCampaignSegmentsCache implements ProcessItemDataContract
{
    use AppUserCacheItem;

    public function __construct($company_id, $itemKey = [])
    {
        $this->companyId = $company_id;
        $this->itemKey($itemKey);
    }

    /**
     * Set cache key for data.
     *
     * @param array $params
     *
     * @return string
     */
    public function itemKey($params)
    {
        $this->itemCacheKey = "company_{$this->companyId}_appgroup_{$params['app_group_id']}_campaign_{$params['campaign_id']}_segments";
    }
}
