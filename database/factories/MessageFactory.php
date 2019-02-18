<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

$factory->define(App\Message::class, function (Faker $faker) {
    $maxOffsetSeconds = Carbon::now('America/Chicago')
    ->diffInSeconds(Carbon::createFromTime(4, 0, 0, 'America/Chicago'));
    $inOutMessages = ['@back','@in','@out','@lunch','@brb'];

    return [
        'type' => 'message',
        'text' => $faker->optional(0.85, $faker->randomElement($inOutMessages))->sentence(),
        'user' => '',
        'ts' => time() - mt_rand(0, $maxOffsetSeconds),
      ];
});
