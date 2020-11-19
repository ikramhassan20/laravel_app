<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\CampaignVariant;
use App\Translation;
use Illuminate\Support\Facades\DB;
use App\Cache\CacheKeys;
use App\Cache\CampaignTranslationCache;

class ComposeStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, Campaign $campaign)
    {
        $data['variants'] = json_decode(base64_decode($data['variants']), true);

        if (array_search(Campaign::STEP_COMPOSE, Campaign::STEP_LEVEL) > array_search($campaign->step, Campaign::STEP_LEVEL))
            $campaign->step = Campaign::STEP_COMPOSE;

        if (!empty($data['variants'])) {
            $cacheKey = new CacheKeys($campaign->app_group_id);
            $campaignTrackingCache = new CampaignTranslationCache();
            $distributions = [];
            $previousVariants = CampaignVariant::where("campaign_id", $campaign->id)->get();

            if (sizeof($previousVariants) > 0) {
                $variantIds = [];
                foreach ($previousVariants as $variant) {
                    $variantIds[] = $variant->id;
                }
                $templatesKeys = Translation::whereIn("translatable_id", $variantIds)->pluck('template')->toArray();

                foreach ($templatesKeys as $campaignCacheKey) {
                    $campaignTrackingCache->removeEntry($campaignCacheKey);
                }

                Translation::whereIn("translatable_id", $variantIds)->delete();
                $distributions = CampaignVariant::where("campaign_id", $campaign->id)->select("distribution")->get();
                CampaignVariant::where("campaign_id", $campaign->id)->delete();
            }

            $i = 0;
            $totalVariants = sizeof($data['variants']);
            $totalDistributions = sizeof($distributions);

            foreach ($data['variants'] as $variant) {
                $campaignVariant = new CampaignVariant();
                $campaignVariant->campaign_id = $campaign->id;

                if ($totalVariants != $totalDistributions) {
                    $campaignVariant->distribution = (float)(100 / $totalVariants);
                } else {
                    $campaignVariant->distribution = $distributions[$i]->distribution;
                    $i++;
                }

                $campaignVariant->message_type_id = $variant["messageType"]["id"];
                $campaignVariant->orientation_id = $variant["orientation"]["id"];
                $campaignVariant->position_id = $variant["position"]["id"];
                $campaignVariant->platform_id = $variant["platform"]["id"];
                $campaignVariant->save();

                $langItr = 0;
                $languageTemplates = $variant['lang'];
                foreach ($variant['totalLang'] as $languages) {
                    $translation = new Translation();
                    $translation->language_id = $languages["id"];
                    $translation->translatable_id = $campaignVariant->id;
                    $translation->translatable_type = "campaign";
                    $key = $cacheKey->generateCampaignTranslationKey($campaign->id, $languages["id"], $campaignVariant->id);

                    /*if ($campaign->campaign_type == Campaign::CAMPAIGN_EMAIL_CODE) {
                        $match = [];
                        preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/', $languageTemplates[$langItr]['templateInfo']['template'], $match);
                        foreach ($match[2] as $url) {
                            $languageTemplates[$langItr]['templateInfo']['template'] = str_replace($url,
                                url('') . '/trackLink?enc=' . base64_encode("email" . '/' . $campaign->id . '/' . $url),
                                $languageTemplates[$langItr]['templateInfo']['template']);
                        }
                    }*/

                    $translation->template = $key;
                    $campaignTrackingCache->setCampaignTranslationCache($campaign->app_group_id, $campaign->id, $languages["id"], $campaignVariant->id, $languageTemplates[$langItr]);

                    $translation->save();

                    $langItr++;
                }
            }
        }
        $campaign->save();
        return $campaign->fresh();
    }

    public function getStep($campaignId)
    {

        //$campaignType = Campaign::where("id", $campaignId)->first()->campaign_type;

        $variantsArray = [];
        $variants = CampaignVariant::where("campaign_id", $campaignId)
            ->get();

        foreach ($variants as $variant) {
            $variantObj = (object)[];
            $variantObj->distribution = $variant->distribution;
            $variantObj->platform = DB::table("lookup")->where("id", $variant->platform_id)->select("id", "name")->first();
            $variantObj->messageType = DB::table("lookup")->where("id", $variant->message_type_id)->select("id", "name")->first();
            $variantObj->orientation = DB::table("lookup")->where("id", $variant->orientation_id)->select("id", "name")->first();
            $variantObj->position = DB::table("lookup")->where("id", $variant->position_id)->select("id", "name")->first();
            $variantObj->totalLang = DB::table("translation")
                ->join("language", "translation.language_id", "=", "language.id")
                ->where("translation.translatable_id", $variant->id)
                ->where("translation.translatable_type", "campaign")
                ->select("language.id", "language.name as label", "language.code as value", "language.image as imgUrl", "dir")
                ->get();

            $variantObj->lang = [];
            $languages = DB::table("translation")
                ->where("translation.translatable_id", $variant->id)
                ->where("translation.translatable_type", "campaign")
                ->select("translation.template")
                ->get();

            //$campaignTrackingCache = new CampaignTranslationCache();

            foreach ($languages as $language) {
                //$langObj = $campaignTrackingCache->getCampaignTranslationCache($language->template);

                $langObj = !empty(\Cache::get($language->template)) ? \GuzzleHttp\json_decode(\Cache::get($language->template)) : '';
                /*if ($campaignType == Campaign::CAMPAIGN_EMAIL_CODE) {
                    $match = [];
                    preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/', $langObj->templateInfo->template, $match);

                    foreach ($match[2] as $url) {
                        $langObj->templateInfo->template = str_replace($url,
                            str_replace("email/" . $campaignId . "/", "", base64_decode(explode("/trackLink?enc=", $url)[1])),
                            $langObj->templateInfo->template
                        );
                    }
                }*/

                $variantObj->lang[] = $langObj;
            }

            $variantsArray[] = $variantObj;
        }

        return $variantsArray;
    }

}