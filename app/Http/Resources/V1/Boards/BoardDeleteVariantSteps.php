<?php


namespace App\Http\Resources\V1\Boards;


use App\Board;
use App\Cache\CacheKeys;
use App\Cache\CampaignTranslationCache;
use App\Translation;
use App\VariantStep;

class BoardDeleteVariantSteps
{
    public function process($data, Board $board)
    {
        $campaignTrackingCache = new CampaignTranslationCache();
        $templatesKeys = Translation::select('template')->where("translatable_id",'=',$data['variant_step_id'])->first();
        $campaignTrackingCache->removeEntry($templatesKeys->template);
        Translation::where("translatable_id", $data['variant_step_id'])->delete();
        $variantStep = VariantStep::where('id', '=', $data['variant_step_id'])->delete();
        return $board->refresh();
    }
}