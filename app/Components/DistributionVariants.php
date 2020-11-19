<?php

namespace App\Components;

use App\CampaignVariant;
use App\BoardVariant;
use Log;

class DistributionVariants
{
    /**
     * divide all provided users for a campaign
     * into variant based chunks
     *
     * @param @array $rowId
     * @param @int $campaignId
     *
     * @return array
     * */
    public static function distribution($rowId, $campaignId)
    {
        try {
            $skip = 0;
            $totalUsers = count($rowId);
            $variantItr = 1;
            $variantsDistribution = CampaignVariant::where("campaign_id", $campaignId)
                                                        ->select("id","distribution")->orderBy('id', 'asc')->get();
            $variants = [];
            foreach ($variantsDistribution as $distribution) {
                $variantUserCount = ceil(($distribution->distribution / 100) * $totalUsers);
                $currentVariant = [
                    'id' => $distribution->id,
                    'row_ids' => []
                ];
                for ($i = 0; $i < $variantUserCount; $i++) {
                    if (isset($rowId[$skip + $i]))
                        $currentVariant['row_ids'][] = $rowId[$skip + $i];
                }
                $skip = $skip + $variantUserCount;
                $variants[] = $currentVariant;
                $variantItr++;
            }
            return $variants;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }


    public static function distribution_board_variants($rowIds, $board_id)
    {
        try {
            $skip = 0;
            $totalUsers = count($rowIds);
            $variantItr = 1;
            $variantsDistribution = BoardVariant::where("board_id", $board_id)
                ->select("id", "distribution", "variant_type", "from_name", "from_email", "subject")->orderBy('id', 'asc')->get();
            $variants = [];
            foreach ($variantsDistribution as $distribution) {
                // get variant steps
                $steps = $distribution->steps()->select('id')->orderBy('id', 'asc')->get()->toArray();
                $variantUserCount = ceil(($distribution->distribution / 100) * $totalUsers);
                $currentVariant = [
                    'id' => $distribution->id,
                    'variant_type' => $distribution->variant_type,
                    'from_name' => $distribution->from_name,
                    'from_email' => $distribution->from_email,
                    'subject' => $distribution->subject,
                    'steps' => $steps,
                    'row_ids' => []
                ];
                for ($i = 0; $i < $variantUserCount; $i++) {
                    if (isset($rowIds[$skip + $i]))
                        $currentVariant['row_ids'][] = $rowIds[$skip + $i];
                }
                $skip = $skip + $variantUserCount;
                $variants[] = $currentVariant;
                $variantItr++;
            }

            return $variants;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            Log::error($exception->getMessage());
        }
    }


}