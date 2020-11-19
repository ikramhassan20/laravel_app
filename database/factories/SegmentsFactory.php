<?php

use Faker\Generator as Faker;

$factory->define(\App\Segment::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'app_group_id' => 1,
        'is_active' => true
    ];
});
