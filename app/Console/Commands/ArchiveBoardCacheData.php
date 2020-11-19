<?php

namespace App\Console\Commands;

use App\Board;
use App\BoardTracking;
use App\Language;
use App\Translation;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ArchiveBoardCacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'board:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to remove board specific cache data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $id = $this->argument('id');

            $boardData = Board::select('app_group_id')
                ->where('id', $id)
                ->first();
            if ($boardData) {
                $appGroupID = $boardData->app_group_id;

                $languages = Language::select(['id', 'name', 'code'])->get();

                $languagesArray = [];
                foreach ($languages as $language) {
                    $languagesArray[$language->id] = $language->code;
                }

                self::removeEntry("board_" . $id . "_segments");
                self::removeEntry("app_group_id_" . $appGroupID . "_board_id_" . $id . "_segments_union");
                self::removeEntry('app_group_id_' . $appGroupID . '_once_board_id_' . $id . '_rows');

                $boardTrackingIDs = [];
                $boardTrackings = BoardTracking::select(['id', 'row_id', 'variant_step_id', 'language_id'])->where('board_id', $id)->get();
                foreach ($boardTrackings as $boardTracking) {
                    array_push($boardTrackingIDs, $boardTracking->id);
                    self::removeEntry("board_tracking_board_id_" . $id . "_row_id_" . $boardTracking->row_id . '_language_' . $languagesArray[$boardTracking->language_id] . "_variant_step_id" . $boardTracking->variant_step_id);
                    self::removeEntry("app_group_id_" . $appGroupID . "_board_" . $id . "_language_" . $languagesArray[$boardTracking->language_id] . "_variant_" . $boardTracking->variant_step_id);

                }

                $translations = Translation::select(['id', 'template'])
                    ->where('translatable_id', $id)
                    ->where('translatable_type', 'board')
                    ->get();

                foreach ($translations as $translation) {
                    self::removeEntry($translation->template);
                }

                // Deleting Board Tracings
                BoardTracking::whereIn('id', $boardTrackingIDs)->forceDelete();

                Translation::where('translatable_id', $id)->where('translatable_type', 'board')->forceDelete();

                Board::where('id', $id)->forceDelete();


                echo 'Board Removed Successfully';

            } else {
                echo 'Board not found!';
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

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

    protected function configure()
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Board ID');
    }
}
