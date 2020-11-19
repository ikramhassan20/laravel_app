<?php

use Illuminate\Database\Seeder;
// use Carbon;
use Carbon\Carbon;

class PermissionsTableSeeder extends Seeder
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
            $disk->get('permissions.json'),
            true
        );

        foreach ($items as $item) {
            \App\Permission::create($item);
        }
    }
}
