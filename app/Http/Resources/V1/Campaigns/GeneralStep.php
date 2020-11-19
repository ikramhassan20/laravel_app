<?php

namespace App\Http\Resources\V1\Campaigns;

use App\Campaign;
use App\CampaignCapRule;
use App\CampaignVariant;
use App\Language;
use App\Translation;
use App\UserPackageHistory;
use Illuminate\Support\Facades\DB;
use App\Components\RandomString;
use App\Cache\CacheKeys;
use App\Cache\CampaignTranslationCache;


class GeneralStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, Campaign $campaign)
    {
        try {

            if (isset($data['selectedTemplate']['content'])) {
                $data['selectedTemplate']['content'] = base64_decode($data['selectedTemplate']['content']);
            }

            $user = request()->user();
            $campaignId = [];

            if (!isset($campaign->id)) {

                /*$packageUsed = [
                    "push" => 99999999,
                    "inapp" => 999999999,
                    "email" => 99999999999,
                    "nfc" => 999999
                ];

                $limitExist = UserPackageHistory::join("package", "user_package_history.package_id", "=", "package.id")
                    ->where("user_package_history.user_id", $user->id)
                    ->where("user_package_history.is_active", 1)
                    ->where("package." . $data['selectedCampaignType']["code"] . "_limit", ">", $packageUsed[$data['selectedCampaignType']["code"]])
                    ->first();


                if (!$limitExist) {
                    return [
                        "dialogueOpen" => "true",
                        "status" => false,
                        "message" => $data['selectedCampaignType']["code"] . " campaign limit reached, cannot make more " . $data['selectedCampaignType']["code"] . " campaigns"
                    ];
                }*/


                $app_group = $user->currentAppGroup();
                $campaign->code = RandomString::generateWithPrefix('campaign');
                $campaign->created_by = $user->id;
                $campaign->app_group_id = $app_group->id;
                $campaign->step = Campaign::STEP_GENERAL;
            } else {
                $campaignId[] = $campaign->id;
            }

            $duplicateRecord = Campaign::where("app_group_id", $user->currentAppGroup()->id)
                ->where("name", $data['name'])
                ->whereNotIn("id", $campaignId)
                ->first();

            if ($duplicateRecord) {
                return [
                    "dialogueOpen" => "true",
                    "status" => false,
                    "message" => "Campaign Name Already Exist"
                ];
            }


            $campaign->name = $data['name'];
            $campaign->updated_by = $user->id;
            $campaign->subject = isset($data['subject']) ? $data['subject'] : '';
            $campaign->from_name = isset($data['fromName']) ? $data['fromName'] : '';
            $campaign->from_email = isset($data['fromEmail']) ? $data['fromEmail'] : '';
            $campaign->campaign_type = $data['selectedCampaignType']["code"];
            $campaign->tags = implode(",", $data['tagsOriginal']);
            $campaign->save();

            if ($data["selectedCampaignType"]["code"] == Campaign::CAMPAIGN_EMAIL_CODE && sizeof($data['selectedTemplate']) > 0) {

                $cacheKey = new CacheKeys($campaign->app_group_id);
                $campaignTrackingCache = new CampaignTranslationCache();


                $englishId = Language::where('code', 'en')->first()->id;
                $previousVariants = CampaignVariant::where("campaign_id", $campaign->id)->get();

                $deletedVariantIds = [];
                $nonDeletedVariantIds = [];
                $itr = 0;
                foreach ($previousVariants as $variant) {
                    if ($itr == 0) {
                        $nonDeletedVariantIds[] = $variant->id;
                    } else {
                        $deletedVariantIds[] = $variant->id;
                    }
                    $itr++;
                }

                $templatesKeys1 = Translation::whereIn("translatable_id", $nonDeletedVariantIds)->where("language_id", "<>", $englishId)->pluck('template')->toArray();
                $templatesKeys2 = Translation::whereIn("translatable_id", $deletedVariantIds)->pluck('template')->toArray();
                $templatesKeys = array_merge($templatesKeys1, $templatesKeys2);

                foreach ($templatesKeys as $campaignCacheKey) {
                    $campaignTrackingCache->removeEntry($campaignCacheKey);
                }

                Translation::whereIn("translatable_id", $nonDeletedVariantIds)->where("language_id", "<>", $englishId)->delete();
                Translation::whereIn("translatable_id", $deletedVariantIds)->delete();

                CampaignVariant::whereIn("id", $deletedVariantIds)->delete();

                if (sizeof($campaignId) > 0) {
                    foreach ($nonDeletedVariantIds as $variant) {
                        $campaignVariant = CampaignVariant::where("id", $variant)->first();
                        $campaignVariant->distribution = 100;
                        $campaignVariant->save();

                        $campaignTranslation = Translation::where("translatable_id", $variant)->first();

                        $isJson = is_string($campaignTranslation->template) && is_array(json_decode($campaignTranslation->template, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;

                        if ($isJson) {
                            $template = \GuzzleHttp\json_decode($campaignTranslation->template, true);
                            $template['templateInfo']['template'] = $data['selectedTemplate']['content'];

                            $key = $cacheKey->generateCampaignTranslationKey($campaign->id, $englishId, $campaignVariant->id);
                            $campaignTrackingCache->setCampaignTranslationCache($campaign->app_group_id, $campaign->id, $englishId, $campaignVariant->id, \GuzzleHttp\json_encode($template));


                            //$campaignTranslation->template = \GuzzleHttp\json_encode($template);
                            $campaignTranslation->template = $key;


                        } else {
                            $key = $cacheKey->generateCampaignTranslationKey($campaign->id, $englishId, $campaignVariant->id);
                            $campaignTrackingCache->setCampaignTranslationCache($campaign->app_group_id, $campaign->id, $englishId, $campaignVariant->id, $data['selectedTemplate']['content']);
                            //$campaignTranslation->template = $data['selectedTemplate']['content'];
                            $campaignTranslation->template = $key;
                        }

                        $campaignTranslation->save();
                    }

                } else {
                    $campaignVariant = new CampaignVariant();
                    $campaignVariant->campaign_id = $campaign->id;
                    $campaignVariant->distribution = 100;
                    $campaignVariant->save();


                    $key = $cacheKey->generateCampaignTranslationKey($campaign->id, $englishId, $campaignVariant->id);
                    $campaignTrackingCache->setCampaignTranslationCache($campaign->app_group_id, $campaign->id, $englishId, $campaignVariant->id, $data['selectedTemplate']['content']);

                    $translation = new translation();
                    $translation->language_id = $englishId;
                    $translation->translatable_id = $campaignVariant->id;
                    $translation->translatable_type = "campaign";
                    //$translation->template = $data['selectedTemplate']['content'];
                    $translation->template = $key;
                    $translation->save();
                }

            }
            $campaign = $campaign->fresh();
            $campaign->frequencyCapController = CampaignCapRule::where('app_group_id', $user->currentAppGroup()->id)
                ->where("campaign_type", $campaign->campaign_type)
                ->first() ? true : false;

            return $campaign;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

    }

    public function getStep($campaignId)
    {

        $generalStep = DB::table("campaign")
            ->where('id', $campaignId)
            ->select("name", "code", "campaign_type as selectedCampaignType", "tags as tagsOriginal", "from_name as fromName", "from_email as fromEmail", "subject", "status", "step as currentStep", "updated_at")
            ->first();

        //dd($generalStep);
        $generalStep->tagsOriginal = $generalStep->tagsOriginal == "" ? [] : explode(",", $generalStep->tagsOriginal);
        $generalStep->step = "general";

        $campaignTypeCode = $generalStep->selectedCampaignType;
        $generalStep->selectedCampaignType = (object)[];
        $generalStep->selectedCampaignType->code = $campaignTypeCode;

        foreach (Campaign::CAMPAIGN_TYPES as $type) {
            if ($type["code"] == $campaignTypeCode) {
                $generalStep->selectedCampaignType->name = $type["name"];
                break;
            }
        }
        $generalStep->selectedTemplate = (object)[];

        if ($campaignTypeCode == Campaign::CAMPAIGN_EMAIL_CODE && $generalStep->currentStep == "general") {

            $variantId = CampaignVariant::where("campaign_id", $campaignId)->first()->id;
            //$generalStep->selectedTemplate->emailContent = Translation::where("translatable_id", $variantId)->first()->template;
            $generalStep->selectedTemplate->emailContent = \GuzzleHttp\json_decode(\Cache::get(Translation::where("translatable_id", $variantId)->first()->template));
        }

        unset($generalStep->currentStep);

        $generalStep->frequencyCapController = CampaignCapRule::where('app_group_id', request()->user()->currentAppGroup()->id)
            ->where("campaign_type", $generalStep->selectedCampaignType->code)
            ->first() ? true : false;

        return $generalStep;
    }
}