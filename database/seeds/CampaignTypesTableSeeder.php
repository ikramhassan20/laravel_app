<?php

use Illuminate\Database\Seeder;

class CampaignTypesTableSeeder extends Seeder
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
            $disk->get('campaign_type.json'),
            true
        );

        foreach ($items as $item) {
            \App\CampaignTypes::create($item);
        }
    }
}
