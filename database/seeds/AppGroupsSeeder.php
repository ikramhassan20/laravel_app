<?php

use Illuminate\Database\Seeder;

class AppGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Components\DatabaseFactory::create(\App\AppGroup::class, [
            'company_id'    => 2,
            'name'          => 'Default',
            'code'          => str_random(20),
            'is_default'       => true
        ]);
    }
}
