<?php

namespace Tests\Fakes;

use App\Message;

class FakeSlackClient
{
    public function __construct($teamId)
    {
        //
    }

    public function getUsers()
    {
        return SlackUser::where('team_id', $this->teamId)
            ->get()
            ->mapWithKeys(
                function ($user) {
                    return [$user->slack_id => $user];
                }
            );
    }

    public function getMessagesFromToday($channelId)
    {
        return Message::all();
    }
}
