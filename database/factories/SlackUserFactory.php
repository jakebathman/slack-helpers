<?php

namespace Database\Factories;

use App\SlackUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlackUserFactory extends Factory
{
    protected $model = SlackUser::class;

    public function definition(): array
    {
        return [
            'slack_id' => 'U' . $this->faker->regexify('[A-Z0-9]{8}'),
            'team_id' => 'T' . $this->faker->regexify('[A-Z0-9]{8}'),
            'display_name' => $this->faker->userName,
            'color' => substr($this->faker->hexColor, 1),
            'real_name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'tz' => 'America/Chicago',
            'updated' => time() - mt_rand(0, 24 * 60 * 60),
        ];
    }
}
