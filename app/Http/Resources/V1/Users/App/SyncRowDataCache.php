<?php

namespace App\Http\Resources\V1\Users\App;

use App\Concerns\Http\Resources\AppUserCacheItem;
use App\Http\Resources\Contracts\ProcessItemDataContract;

class SyncRowDataCache implements ProcessItemDataContract
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
        $this->itemCacheKey = "company_{$this->companyId}_row_{$params['row_id']}_data";
    }

    /**
     * Create/update data in cache.
     *
     * @param array $params
     *
     * @return array
     */
    public function save($params)
    {
        $this->setItemData($params);
    }
}
