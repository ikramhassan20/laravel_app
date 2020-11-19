<?php

namespace App\Http\Resources\Contracts;

interface ProcessItemDataContract
{
    /**
     * Check whether data exists in cache.
     *
     * @return bool
     */
    public function exists();

    /**
     * Create/update data in cache.
     *
     * @param array $params
     *
     * @return array
     */
    public function save($params);

    /**
     * Set cache key for data.
     *
     * @param array $params
     *
     * @return string
     */
    public function itemKey($params);

    /**
     * Get cache data.
     *
     * @return array
     */
    public function getItemData();

    /**
     * Set cache data.
     *
     * @param array  $params
     *
     * @return bool
     */
    public function setItemData($params);
}
