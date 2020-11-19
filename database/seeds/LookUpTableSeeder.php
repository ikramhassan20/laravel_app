<?php

use Illuminate\Database\Seeder;

class LookUpTableSeeder extends Seeder
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
            $disk->get('lookup.json'),
            true
        );

        foreach ($items as $item) {
            $item['app_group_id'] = 1;
            $item['created_by'] = 2;
            $item['updated_by'] = 2;
            \App\Lookup::create($item);
        }
    }
}
