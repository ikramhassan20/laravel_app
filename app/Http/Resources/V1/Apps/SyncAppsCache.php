<?php

namespace App\Http\Resources\V1\Apps;

use App\Concerns\Http\Resources\AppUserCacheItem;
use App\Http\Resources\Contracts\ProcessItemDataContract;

class SyncAppsCache implements ProcessItemDataContract
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
        $this->itemCacheKey = "company_{$this->companyId}_apps";
    }

    /**
     * Create/update data in cache.
     *
     * @param array $params
     */
    public function save($params)
    {
        $this->setItemData($params);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function activeApps()
    {
        $items = collect($this->getItemData());

        if ($items->count() > 0) {
            $items = $items->filter(function ($item) {
                return ((bool)$item['is_active'] === true) ? $item : null;
            });
        }

        return $items;
    }
}