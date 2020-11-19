<?php
namespace App\Cache;

use App\Apps;
use App\Package;
use App\AppUserTokens;
use App\Helpers\CommonHelper;
use App\Cache\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyPackagesCache{

    /**
     * save company package usage cache
     *
     * @param string $package
     * @param int $push
     * @param int $inapp
     * @param int $email
     * @param int $newsfeed
     * @param int $attribute
     *
     * @return object
     */
    public function saveCompanyPackageUsageCache($package, $push, $inapp, $email, $newsfeed, $attribute)
    {


        return object;
    }

    /**
     * save company package usage cache
     *
     * @param string $package
     * @param int $push
     * @param int $inapp
     * @param int $email
     * @param int $newsfeed
     * @param int $attribute
     *
     * @return object
     */
    public function getCompanyPackageUsageCache($package, $push, $inapp, $email, $newsfeed, $attribute)
    {

        return object;
    }

    /**
     * Removes entry from cache.
     *
     * @param string cache_key
     */
    public static function removeEntry($cache_key)
    {
        if (!in_array(config('cache.default'), ['array', 'database', 'file', 'redis'])) {
            \Artisan::call('cache:clear', [
                '--tags' => $cache_key
            ]);
        }

        //\Redis::del($cache_key);
        \Cache::forget($cache_key);
    }

}