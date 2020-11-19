<?php

use Illuminate\Database\Seeder;

class CampaignTrackingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $disk = \Storage::disk('seeders');
        $items = \GuzzleHttp\json_decode(
            $disk->get('campaign_tracking.json'),
            true
        );

        foreach ($items as $item) {
            \App\CampaignTracking::create($item);
        }
    }
}
