<?php

use App\Token;
use Faker\Generator as Faker;

$factory->define(Token::class, function (Faker $faker) {
    return [
        'access_token' => 'xoxp-' . $faker->regexify('[a-f0-9]{12}-[a-f0-9]{12}-[a-f0-9]{12}-[a-f0-9]{32}'),
        'scope' => "identify,commands,channels:history,users:read,chat:write:bot",
        'user_id' => 'U' . $faker->regexify('[A-Z0-9]{8}'),
        'team_name' => $faker->company,
        'team_id' => 'T' . $faker->regexify('[A-Z0-9]{8}'),
    ];
});
