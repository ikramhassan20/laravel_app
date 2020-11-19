<?php

use Illuminate\Database\Seeder;

class CampaignTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $disk = \Storage::disk('seeders');
        $items = \GuzzleHttp\json_decode(
            $disk->get('campaign.json'),
            true
        );
        foreach ($items as $item) {
            \App\Campaign::create($item);
        }

    }
}
