<?php

namespace App\Http\Resources\V1\Boards;

use App\Board;
use App\Cache\CacheKeys;
use App\Cache\CampaignTranslationCache;
use App\CampaignVariant;
use App\Components\InteractsWithMessages;
use App\Translation;
use App\VariantStep;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BoardCompose
{
    use InteractsWithMessages;

    public function process($data, Board $board)
    {
        $variant_step_id = 0;
        $finalResponse = array();
        if (array_search(Board::STEP_BUILD, Board::STEP_LEVEL) > array_search($board->step, Board::STEP_LEVEL))
            $board->step = Board::STEP_BUILD;
        $data['variants'] = json_decode(base64_decode($data['variants']), true);
        // dd($data['variants']);
        if (!empty($data['variants'])) {
            $cacheKey = new CacheKeys($board->app_group_id);
            $campaignTrackingCache = new CampaignTranslationCache();
            $distributions = [];
            $previousVariants = \App\BoardVariant::join('board_variant_step', 'board_variant_step.variant_id', '=', 'board_variant.id')
                ->where('board_id', '=', $board->id)
                ->where('board_variant_step.id', '=', $data['variant_step_id'])
                ->get(['board_variant_step.id']);
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
                $langItr = 0;
                $languageTemplates = $data['variants']['lang'];
                foreach ($data['variants']['totalLang'] as $languages) {
                    $translation = new Translation();
                    $translation->language_id = $languages["id"];
                    $translation->translatable_id = $data['variant_step_id'];
                    $translation->translatable_type = "board";
                    $key = $cacheKey->generateBoardTranslationKey($board->app_group_id, $board->id, $languages["id"], $data['variant_step_id']);

                    $translation->template = $key;

                    $campaignTrackingCache->setBoardTranslationCache($board->app_group_id, $board->id, $languages["id"], $data['variant_step_id'], $languageTemplates[$langItr]);

                    $translation->save();

                    $langItr++;
                }
                $boardDelay = self::boardDelay($data["variant_id"], $data["delayNo"], $data["delayType"], $data['variant_step_id']);
                $variant_step_id = $data['variant_step_id'];
                VariantStep::where("id", $data['variant_step_id'])->update([
                    'step_control_delay_value' => $data["delayNo"],
                    'step_control_delay_unit' => $data["delayType"],
                    'board_delay' => $boardDelay
                ]);
                //  \App\BoardVariant::where("board_id", $board->id)->delete();
            } else {
                $i = 0;
                $totalVariants = sizeof($data['variants']);
                $totalDistributions = sizeof($distributions);
                $variantSteps = new VariantStep();
                $variantSteps->variant_id = $data["variant_id"];
                $variantSteps->message_type_id = $data['variants']["messageType"]["id"];
                $variantSteps->orientation_id = $data['variants']["orientation"]["id"];
                $variantSteps->position_id = $data['variants']["position"]["id"];
                $variantSteps->platform_id = $data['variants']["platform"]["id"];
                $variantSteps->step_control_delay_value = $data["delayNo"];
                $variantSteps->step_control_delay_unit = $data["delayType"];
                $variantSteps->variant_step_number = $data["stepNo"];
                $variantSteps->save();
                $variant_step_id = $variantSteps->id;
                $boardDelay = self::boardDelay($data["variant_id"], $data["delayNo"], $data["delayType"], $variant_step_id);
                $variantSteps->id = $variant_step_id;
                $variantSteps->board_delay = $boardDelay;
                $variantSteps->save();
                $langItr = 0;
                $languageTemplates = $data['variants']['lang'];
                foreach ($data['variants']['totalLang'] as $languages) {
                    $translation = new Translation();
                    $translation->language_id = $languages["id"];
                    $translation->translatable_id = $variantSteps->id;
                    $translation->translatable_type = "board";
                    $key = $cacheKey->generateBoardTranslationKey($board->app_group_id, $board->id, $languages["id"], $variantSteps->id);

                    $translation->template = $key;

                    $campaignTrackingCache->setBoardTranslationCache($board->app_group_id, $board->id, $languages["id"], $variantSteps->id, $languageTemplates[$langItr]);

                    $translation->save();

                    $langItr++;
//                }
                }
            }
        }
        $board->save();
        $finalResponse = array(
            'variant_step_id' => $variant_step_id,
            'board' => $board->fresh()
        );
        return $finalResponse;
    }

    public static function boardDelay($variant_id, $delayNo, $delayType, $varaint_step_id)
    {
        $delayValue = 0;
        $boardVariantLastStep = [];
        if ($varaint_step_id) {
            $varaint_step_id = $varaint_step_id - 1;
            if ($varaint_step_id != 0) {
                $boardVariantLastStep = VariantStep::where('variant_id', '=', $variant_id)
                    ->where('id', '=', $varaint_step_id)
                    ->get();
            }
        } else {
            $boardVariantLastStep = VariantStep::where('variant_id', '=', $variant_id)
                ->get();
        }
        if (count($boardVariantLastStep) > 0) {
            if ($boardVariantLastStep->last()) {
                $boardSeconds = self::getSeconds($delayNo, $delayType);
                $delayValue = $boardVariantLastStep->last()->board_delay + $boardSeconds;
            }
        }
        return $delayValue;
    }

    public static function getSeconds($value, $unit)
    {
        $timeUnit = [
            "minute" => 60,
            "hour" => 3600,
            "day" => 86400,
            "week" => 604800,
            "month" => 2592000
        ];
        $seconds = $value * $timeUnit[$unit];
        return $seconds;
    }

    public function getStep($boardId)
    {
        $variantsArray = [];
        $BoardVariants = \App\BoardVariant::where("board_id", '=', $boardId)->get();
        if (count($BoardVariants) > 0) {
            foreach ($BoardVariants as $variant) {
                $variantSteps = VariantStep::where('variant_id', '=', $variant->id)->get();
                $variantObj = [];
                $Steps = [];
                if (count($variantSteps) > 0) {
                    foreach ($variantSteps as $variantStepObj) {
                        $stepCountCompleted = $variantStepObj->trackingRowIds()->where('status', 'completed')->count();
                        $stepCountFailed = $variantStepObj->trackingRowIds()->where('status', 'failed')->count();
                        $stepShowStats = $variantStepObj->trackingRowIds()->exists();
                        $objTracking = $variantStepObj->trackingRowIds()->first();
                        if($objTracking){
                            $sentAt =  $objTracking->sent_at;
                        }
                        else{
                            $sentAt = NULL;
                        }
                        $platform = DB::table("lookup")->where("id", $variantStepObj->platform_id)->select("id", "name")->first();
                        $messageType = DB::table("lookup")->where("id", $variantStepObj->message_type_id)->select("id", "name")->first();
                        $orientation = DB::table("lookup")->where("id", $variantStepObj->orientation_id)->select("id", "name")->first();
                        $position = DB::table("lookup")->where("id", $variantStepObj->position_id)->select("id", "name")->first();
                        $totalLang = DB::table("translation")
                            ->join("language", "translation.language_id", "=", "language.id")
                            ->where("translation.translatable_id", $variantStepObj->id)
                            ->where("translation.translatable_type", "board")
                            ->select("language.id", "language.name as label", "language.code as value", "language.image as imgUrl", "dir")
                            ->get();
                        $languages = DB::table("translation")
                            ->where("translation.translatable_id", $variantStepObj->id)
                            ->where("translation.translatable_type", "board")
                            ->select("translation.template")
                            ->get();
                        $lang = array();
                        foreach ($languages as $language) {
                            if (\Cache::get($language->template)) {
                                $langObj = \GuzzleHttp\json_decode(\Cache::get($language->template));
                                $lang[] = $langObj;
                            }
                        }
                        $Steps = array(
                            'platform' => $platform,
                            'messageType' => $messageType,
                            'orientation' => $orientation,
                            'position' => $position,
                            'totalLang' => $totalLang,
                            'lang' => $lang,
                        );
                        $variantObj[] = array(
                            'variant_step_id' => $variantStepObj->id,
                            'delayNo' => $variantStepObj->step_control_delay_value,
                            'delayType' => $variantStepObj->step_control_delay_unit,
                            'completed' => $stepCountCompleted,
                            'failed' => $stepCountFailed,
                            'showStats' => $stepShowStats,
                            'sentAt' => $sentAt,
                            'stepDetails' => $Steps
                        );
                    }
                }
                $fromEmailValidationStatus = true;
                if ($variant->variant_type == Board::BOARD_EMAIL_CODE) {
                    $fromEmailValidationStatus = $this->getFromEmailValidationStatus($variant);
                }

                $variantsArray[] = array(
                    'variant_id' => $variant->id,
                    'board_id' => $variant->board_id,
                    'variant_type' => $variant->variant_type,
                    'distribution' => $variant->distribution,
                    'from_email' => $variant->from_email,
                    'from_name' => $variant->from_name,
                    'subject' => $variant->subject,
                    'variant_steps' => $variantObj,
                    'isFromEmailValid' => $fromEmailValidationStatus
                );
            }
        }
        return $variantsArray;
    }

    public function getFromEmailValidationStatus($boardData)
    {
        $status = true;

        if (!empty(config('mail.verify_to_email'))) {
            $testFromEmail = $this->sendEmail([
                'email_from' => !empty($boardData->from_email) ? $boardData->from_email : config('mail.from.address'),
                'email_from_name' => $boardData->from_name,
                'to_email' => config('mail.verify_to_email'),
                'email_subject' => 'Verifying Email',
                'email_body' => 'This is just test email to verify ' . $boardData->from_email
            ]);

            if ($testFromEmail['status'] == 'error') {
                $status = false;
            }
        }

        return $status;
    }
}