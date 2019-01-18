<?php

namespace App;

use DateTime;
use Wgmv\SlackApi\Facades\SlackChannel;


class SlackClient
{
    protected $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    public function getUsers()
    {
        return SlackUser::all()->mapWithKeys(function ($user) {
            return [$user->slack_id => $user];
        });
    }

    public function getMessagesFromToday()
    {
        $earliestTime = (new DateTime())->setTime(8, 0)->format('U');
        $allMessages = collect();
        $earliestTs = null;
        $latest = null;
        $i = 0;

        while ($earliestTs === null) {
            if ($i++ >= 5) {
                $earliestTs = $latest;
            }

            $data = SlackChannel::history(
                $this->groupId,
                200,
                $latest
            );

            $messages = collect((array)$data->messages)
                ->filter(function ($message) use ($earliestTime) {
                    // Keeps messages after $earliestTime
                    return (int)$message->ts >= $earliestTime;
                });

            $lastMessageTs = $messages->last()->ts;
            $allMessages = $allMessages->merge($messages);
            if ((int)$lastMessageTs > $earliestTime && count($data->messages) == $messages->count()) {
                // API needs to be called again for another batch
                $latest = $lastMessageTs;
                // dump("calling again for before $latest");
                continue;
            }

            $earliestTs = $lastMessageTs;
        }

        return $allMessages;
    }
}
