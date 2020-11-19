<?php

namespace App\Concerns\Http\Resources;

trait AppUserCacheItem
{
    /**
     * @var string
     */
    protected $itemCacheKey;

    /**
     * @var int
     */
    protected $companyId;

    /**
     * Check whether data exists in cache.
     *
     * @return bool
     */
    public function exists()
    {
        $data = $this->getItemData();

        return !empty($data) ? true : false;
    }

    /**
     * Get cache data.
     *
     * @return array
     */
    public function getItemData()
    {
        $data = \Cache::get($this->itemCacheKey);

        return !empty($data) ? \GuzzleHttp\json_decode($data, true) : [];
    }

    /**
     * Set cache data.
     *
     * @param array  $params
     *
     * @return bool
     */
    public function setItemData($params)
    {
        try {
            \Cache::forget($this->itemCacheKey);
            \Cache::forever($this->itemCacheKey, \GuzzleHttp\json_encode($params));

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Create/update data in cache.
     *
     * @param array $params
     *
     * @return array|void
     */
    public function save($params)
    {
        $this->setItemData($params);
    }
}
