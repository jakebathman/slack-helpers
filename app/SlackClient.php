<?php

namespace App;

use App\Exceptions\SlackApiException;
use DateTime;
use Exception;
use Wgmv\SlackApi\Facades\SlackApi;
use Wgmv\SlackApi\Facades\SlackChannel;


class SlackClient
{
    protected $teamId;
    // protected $slack;

    public function __construct($teamId)
    {
        $this->teamId = $teamId;
        $token = Token::where('team_id', $teamId)->first();
        if (!$token) {
            throw new Exception("TeamID {$teamId} not authorized. Install app to Slack at " . url(), 401);
        }
        // $this->slack = new SlackApi(Token::where('team_id', $teamId)->first()->access_token);
    }

    public function getUsers()
    {
        return SlackUser::where('team_id', $this->teamId)
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->slack_id => $user];
            });
    }

    public function getMessagesFromToday($channelId)
    {
        $earliestTime = (new DateTime())->sub(new \DateInterval('P3D'))->setTime(8, 0)->format('U');
        $allMessages = collect();
        $earliestTs = null;
        $latest = null;
        $i = 0;

        while ($earliestTs === null) {
            if ($i++ >= 5) {
                $earliestTs = $latest;
            }

            $data = SlackChannel::history(
                $channelId,
                200,
                $latest
            );

            if ($data->ok == false) {
                throw new SlackApiException("Slack API returned an error while fetching the channel history. Error ID " . $data->error . ". More info at https://api.slack.com/methods/channels.history");
            }

            $messages = collect($data->messages)
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
