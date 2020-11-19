<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(/**
 * @param \Faker\Generator $faker
 * @return array
 */
    App\Campaign::class, function (Faker\Generator $faker) {
    $name = $faker->name;

    return [
        'name' => $name,
        'code' => $name . ' - ' . time(),
        'type_id' => 1,
        'created_by' => 1,
        'updated_by' => 1,
        'company_id' => 20,
    ];
});
