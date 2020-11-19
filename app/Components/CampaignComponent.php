<?php

namespace App\Components;

use App\AppGroup;
use App\Apps;
use App\Campaign;
use App\CampaignQueue;
use App\Helpers\CampaignDeliveryControlHelper;
use App\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

/**
 * Class CampaignComponent
 * @package App\Components
 * @todo NEED TO REWAMP THIS CLASS
 */
class CampaignComponent
{
    /**
     * @param int $itemId
     * @param string $itemType
     * @param int $rowId
     *
     * @throws \Exception
     *
     * @return array
     */
    public function dispatch($itemId, $itemType = 'queue', $rowId = null)
    {
        $response = [
            'campaign_id' => $itemId->campaign_id,
        ];
        return $response;
    }
}
