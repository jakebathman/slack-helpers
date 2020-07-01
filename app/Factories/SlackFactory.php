<?php

namespace App\Factories;

use Faker\Factory;

class SlackFactory
{
    public $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function userId()
    {
        return $this->makeId('user');
    }

    public function teamId()
    {
        return $this->makeId('user');
    }

    public function makeId($type)
    {
        return $this->faker->regexify(ucfirst(substr($type, 0, 1)) . '[A-Z0-9]{7,10}');
    }

    public function makeTs()
    {
        list($usec, $sec) = explode(' ', microtime());

        return $sec . '.' . str_pad(substr($usec, 2, 6), 6, 0);
    }
}
