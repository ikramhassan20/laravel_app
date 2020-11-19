<?php

use Illuminate\Database\Seeder;

class CompanyAppsSeeder extends Seeder
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
            $disk->get('apps.json'),
            true
        );

        foreach ($items as $item) {
            $item['app_group_id'] = 1;
            \App\Apps::create($item);
        }
    }
}
