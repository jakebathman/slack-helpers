<?php

use Faker\Generator as Faker;
use Illuminate\Support\Arr;

$factory->define(App\SlackUser::class, function (Faker $faker) {
    return [
        'slack_id' => 'U' . $faker->regexify('[A-Z0-9]{8}'),
        'team_id' => 'T' . $faker->regexify('[A-Z0-9]{8}'),
        'name' => $faker->userName,
        'color' => substr($faker->hexColor, 1),
        'real_name' => $faker->firstName . " " . $faker->lastName,
        'tz' => 'America/Chicago',
        'updated' => time() - mt_rand(0, 24 * 60 * 60),
    ];
});
