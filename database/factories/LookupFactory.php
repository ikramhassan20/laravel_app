<?php

use Faker\Generator as Faker;

$factory->define(App\Lookup::class, function (Faker $faker) {
    return [
        'app_group_id' => '1',
        'code' =>'2',
        'name'=>$faker->name,
		'description'=>'afaf',
		"parent_id"=>'1',
		"level"=>2,
    ];
});
