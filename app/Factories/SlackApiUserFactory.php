<?php

namespace App\Factories;

use Faker\Factory;

class SlackApiUserFactory
{
    public $firstName;
    public $lastName;
    public $name;
    public $userName;
    public $teamId;
    public $isRestricted;
    public $isUltraRestricted;

    protected $faker;

    public function __construct()
    {
        $this->faker = Factory::create();

        // Set some defaults that are used multiple times
        $this->firstName = $this->faker->firstName;
        $this->lastName = $this->faker->lastName;
        $this->name = $this->firstName . ' ' . $this->lastName;
        $this->userName = $this->faker->userName;
        $this->teamId = $this->faker->regexify('T[A-Z0-9]{7,10}');
    }

    public function isRestricted()
    {
        $this->isRestricted = true;

        return $this;
    }

    public function isUltraRestricted()
    {
        $this->isUltraRestricted = true;

        return $this;
    }

    public function create()
    {

        return collect([
            'id' => $this->userId ?? $this->generateUserId(),
            'team_id' => $this->teamId,
            'name' => $this->userName,
            'deleted' => false,
            'color' => $this->faker->regexify('[0-9abcdef]{6}'),
            'real_name' => $this->name,
            'tz' => 'America/Los_Angeles',
            'tz_label' => 'Pacific Daylight Time',
            'tz_offset' => -25200,
            'profile' => [
                'title' => '',
                'phone' => $this->faker->phoneNumber,
                'skype' => '',
                'real_name' => $this->name,
                'real_name_normalized' => $this->name,
                'display_name' => $this->userName,
                'display_name_normalized' => $this->userName,
                'fields' => [

                ],
                'status_text' => '',
                'status_emoji' => '',
                'status_expiration' => 0,
                'avatar_hash' => $this->faker->regexify('[0-9abcdef]{12}'),
                'guest_invited_by' => $this->generateUserId(),
                'image_original' => $this->faker->url,
                'is_custom_image' => true,
                'email' => $this->faker->email,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'image_24' => $this->faker->url,
                'image_32' => $this->faker->url,
                'image_48' => $this->faker->url,
                'image_72' => $this->faker->url,
                'image_192' => $this->faker->url,
                'image_512' => $this->faker->url,
                'image_1024' => $this->faker->url,
                'status_text_canonical' => '',
                'team' => $this->teamId,
            ],
            'is_admin' => $this->isAdmin ?? false,
            'is_owner' => $this->isOwner ?? false,
            'is_primary_owner' => $this->isPrimaryOwner ?? false,
            'is_restricted' => $this->isRestricted ?? false,
            'is_ultra_restricted' => $this->isUltraRestricted ?? false,
            'is_bot' => $this->isBot ?? false,
            'is_app_user' => $this->isAppUser ?? false,
            'updated' => time(),
        ]);
    }

    public function generateUserId() {
        return $this->faker->regexify('U[A-Z0-9]{7,10}');
    }
}
