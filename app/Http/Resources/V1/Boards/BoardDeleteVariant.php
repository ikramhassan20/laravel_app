<?php


namespace App\Http\Resources\V1\Boards;


use App\Board;
use App\Cache\CampaignTranslationCache;
use App\Translation;
use App\VariantStep;

class BoardDeleteVariant
{
    public function process($data, Board $board)
    {
        $boardVariant = \App\BoardVariant::where('id','=',$data['variant_id'])->delete();
        return $board->refresh();
    }
}