<?php

namespace Database\Factories;

use App\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $maxOffsetSeconds = Carbon::now('America/Chicago')
            ->diffInSeconds(Carbon::createFromTime(4, 0, 0, 'America/Chicago'));
        $inOutMessages = ['@back', '@in', '@out', '@lunch', '@brb'];

        return [
            'client_msg_id' => $this->faker->uuid,
            'type' => 'message',
            'text' => $this->faker->optional(.4, $this->faker->randomElement($inOutMessages))->sentence(),
            'user' => '',
            'ts' => time() - mt_rand(0, $maxOffsetSeconds),
            'team' => '',
            'reactions' => [],
        ];
    }
}
