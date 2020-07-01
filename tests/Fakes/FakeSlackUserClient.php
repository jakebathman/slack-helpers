<?php

namespace Tests\Fakes;

use App\SlackUser;

class FakeSlackUserClient
{

    public function __construct()
    {

    }
    public function info($userId)
    {
        $user = SlackUser::find();

        return (object)[
            'user' => (object)[
            'id' => $user->slack_id,
            'profile' => (object)[
                'display_name' => $user->display_name,
            ],
            'color' => $user->color,
            'real_name' => $user->real_name,
            'tz' => $user->tz,
            'updated' => $user->updated,
            ],
        ];
    }
}
