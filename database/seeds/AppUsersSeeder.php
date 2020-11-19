<?php

use Illuminate\Database\Seeder;

class AppUsersSeeder extends Seeder
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
            $disk->get('apps_users.json'),
            true
        );
        $tokens = $items[0]['tokens'];
        unset($items[0]['tokens']);

        $item = $items[0];
        unset($items);

        \Schema::disableForeignKeyConstraints();

        $apps_user = \App\AppUsers::create($item);

        foreach ($tokens as $token) {
            $token['row_id'] = $apps_user->row_id;
            \App\AppUserTokens::create($token);
        }

        \Schema::enableForeignKeyConstraints();
    }
}
