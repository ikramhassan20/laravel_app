<?php

use Illuminate\Database\Seeder;

class LinkTrackingTableSeeder extends Seeder
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
            $disk->get('link_tracking.json'),
            true
        );
        foreach ($items as $item) {
            \App\LinkTrackings::create($item);
        }
    }
}
