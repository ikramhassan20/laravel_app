<?php

use Faker\Generator as Faker;

$factory->define(App\AppGroup::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'code' => str_random(20),
        'created_by' => 1,
        'updated_by' => 1,
    ];
});
