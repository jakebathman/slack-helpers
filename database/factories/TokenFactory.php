<?php

namespace Database\Factories;

use App\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenFactory extends Factory
{
    protected $model = Token::class;

    public function definition(): array
    {
        return [
            'access_token' => 'xoxp-' . $this->faker->regexify('[a-f0-9]{12}-[a-f0-9]{12}-[a-f0-9]{12}-[a-f0-9]{32}'),
            'scope' => 'identify,commands,channels:history,users:read,chat:write:bot',
            'user_id' => 'U' . $this->faker->regexify('[A-Z0-9]{8}'),
            'team_name' => $this->faker->company,
            'team_id' => 'T' . $this->faker->regexify('[A-Z0-9]{8}'),
        ];
    }
}
