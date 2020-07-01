<?php

namespace App\Factories;

use App\Factories\SlackFactory;

class SlackApiMessageFactory extends SlackFactory
{
    public $amount = 1;
    public $asArray = false;
    public $teamId;
    public $times;
    public $userId;

    public function __construct()
    {
        parent::__construct();

        // Set some defaults that are used multiple times
        $this->teamId = $this->userId();
        $this->userId = $this->teamId();
    }

    public function asArray()
    {
        $this->asArray = true;

        return $this;
    }

    public function withText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function create()
    {
        if ($this->amount <= 1) {
            // Return just one
            $data = $this->make();
        }

        // Return a collection of multiple
        $data = collect(array_map(function () {
            return $this->make();
        }, range(1, $this->amount)));

        if ($this->asArray) {
            return $data->toArray();
        }

        return $data;
    }

    public function make()
    {
        return collect([
            'client_msg_id' => $this->faker->uuid,
            'type' => 'message',
            'text' => $this->text ?? $this->faker->sentence,
            'user' => $this->userId,
            'ts' => $this->makeTs(),
            'team' => $this->teamId,
            'blocks' => [],
        ]);
    }
}
