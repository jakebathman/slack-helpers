<?php

use App\SlackUser;
use Faker\Generator as Faker;

$factory->define(SlackUser::class, function (Faker $faker) {
    return [
        'slack_id' => 'U' . $faker->regexify('[A-Z0-9]{8}'),
        'team_id' => 'T' . $faker->regexify('[A-Z0-9]{8}'),
        'display_name' => $faker->userName,
        'color' => substr($faker->hexColor, 1),
        'real_name' => $faker->firstName . " " . $faker->lastName,
        'tz' => 'America/Chicago',
        'updated' => time() - mt_rand(0, 24 * 60 * 60),
    ];
});
