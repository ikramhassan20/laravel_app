<?php

namespace App\Http\Resources\V1\NewsFeeds;

use App\Language;
use App\NewsFeed;
use App\Components\RandomString;
use App\UserPackageHistory;
use Illuminate\Support\Facades\DB;
use App\Translation;

class ComposeStep
{
    /**
     * @param array $data
     * @param \App\Campaign $campaign
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function process($data, NewsFeed $newsFeed)
    {
        $user = request()->user();
        $newsFeedId = [];

        $data["languageArr"] = json_decode(base64_decode($data['languageArr']), true);

        if (!isset($newsFeed->id)) {

            /*$packageUsed = [
                "push" => 1,
                "inapp" => 1,
                "email" => 1,
                "nfc" => 999999
            ];

            $limitExist = UserPackageHistory::join("package", "user_package_history.package_id", "=", "package.id")
                ->where("user_package_history.user_id", $user->id)
                ->where("user_package_history.is_active", 1)
                ->where("package.nfc_limit", ">", $packageUsed["nfc"])
                ->first();


            if (!$limitExist) {
                return [
                    "dialogueOpen" => "true",
                    "status" => false,
                    "message" => "NFC limit reached, cannot make more NewsFeeds"
                ];
            }*/

            //$newsFeed->code = RandomString::generateWithPrefix('newsfeed');
            $newsFeed->created_by = $user->id;
            $newsFeed->app_group_id = $user->currentAppGroup()->id;
            $newsFeed->step = NewsFeed::STEP_COMPOSE;
            $newsFeed->status = NewsFeed::status_draft;
        } else {
            $newsFeedId[] = $newsFeed->id;
        }


        $duplicateRecord = NewsFeed::where("app_group_id", $user->currentAppGroup()->id)
            ->where("name", $data['name'])
            ->whereNotIn("id", $newsFeedId)
            ->first();

        if ($duplicateRecord) {
            return [
                "dialogueOpen" => "true",
                "status" => false,
                "message" => "NewsFeed Name Already Exist"
            ];
        }


        $newsFeed->name = $data['name'];
        $newsFeed->tags = implode(",", $data['tags']);
        $newsFeed->news_feed_template_id = $data['cardType'];
        $newsFeed->category = $data['category'];


        /*for ($i = 0; $i < sizeof($data['links']); $i++) {
            $data['links'][$i]['value'] = url('') . '/trackLink?enc=' . base64_encode("newsfeed" . '/' . $newsFeed->id . '/' . $data['links'][$i]['value']);
        }*/

        $newsFeed->links = \GuzzleHttp\json_encode($data['links']);

        $newsFeed->save();

        Translation::where("translatable_id", $newsFeed->id)
            ->where("translatable_type", "newsfeed")
            ->delete();


        $itr = 0;
        foreach ($data['totalSelectedLang'] as $lang) {

            $translation = new Translation();
            $translation->language_id = $lang['id'];
            $translation->translatable_id = $newsFeed->id;
            $translation->translatable_type = "newsfeed";
            $translation->template = json_encode($data['languageArr'][$itr]);
            $translation->save();
            $itr++;

        }

        return $newsFeed->fresh();
    }

    public function getStep($newsFeed)
    {
        $step1 = (object)[];
        $step1->step = 'compose';
        $step1->name = $newsFeed->name;
        $step1->tags = $newsFeed->tags == "" ? [] : explode(",", $newsFeed->tags);
        $step1->cardType = $newsFeed->news_feed_template_id;
        $step1->category = $newsFeed->category;
        $step1->totalSelectedLang = [];
        $step1->languageArr = [];
        $step1->links = json_decode($newsFeed->links);

        $data = $this->getNewsFeedLangAndTemplates($newsFeed->id);

        $step1->totalSelectedLang = $data['totalSelectedLang'];
        $step1->languageArr = $data['languageArr'];
        $step1->isActive = $newsFeed->status == NewsFeed::status_draft ? false : true;
        return $step1;
    }

    public function getNewsFeedLangAndTemplates($id)
    {
        $translations = Translation::where("translatable_id", $id)
            ->where("translatable_type", "newsfeed")
            ->get();


        $totalSelectedLang = [];
        $languageArr = [];
        foreach ($translations as $translation) {
            $totalSelectedLang[] = Language::where('id', $translation->language_id)
                ->select('id', 'name as label', 'code as value', 'image as imgUrl', 'dir')
                ->first();
            $languageArr[] = \GuzzleHttp\json_decode($translation->template);
        }

        return [
            "totalSelectedLang" => $totalSelectedLang,
            "languageArr" => $languageArr
        ];
    }

}