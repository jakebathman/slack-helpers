<?php

namespace App\Http\Controllers;

use DateTime;
use App\SlackUser;
use App\SlackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class GetStaffIn extends Controller
{
    protected $generalChannelId;
    protected $client;

    public function __construct()
    {
        $this->generalChannelId = config('services.slack.general_channel_id');
        $this->client = new SlackClient($this->generalChannelId);
        $this->users = $this->client->getUsers();
    }

    public function __invoke(Request $request)
    {
        $messages = $this->client->getMessagesFromToday();

        // Group messages by user and filter only @in/@out/@lunch/@back
        $usersMessages = $messages->filter(function ($message) {
            return preg_match('/(@in|@out|@lunch|@?back)/i', $message->text);
        })
        ->mapToGroups(function ($message) {
            return [
                $message->user => [
                'text' => $message->text,
                'ts' => $message->ts,
                ]
            ];
        });

        $statuses = [];

        foreach ($usersMessages as $userId => $messages) {
            if (!$this->users->has($userId)) {
                // Need to pull and save this user's info from Slack
                $userInfo = SlackUserClient::info($userId)->user;

                $user = SlackUser::create([
                    'slack_id' => $userInfo->id,
                    'team_id' => $userInfo->team_id,
                    'name' => $userInfo->name,
                    'color' => $userInfo->color,
                    'real_name' => $userInfo->real_name,
                    'tz' => $userInfo->tz,
                    'updated' => $userInfo->updated,
                    ]);

                $this->users->put($user->slack_id, $user);
            }

            // Determine status
            $lastMessage = array_get($messages->first(), 'text');
            $lastMessageTs = array_get($messages->first(), 'ts');

            if (preg_match('/(@in)/i', $lastMessage)) {
                $status = "in";
            } elseif (preg_match('/(@out)/i', $lastMessage)) {
                $status = "out";
            } elseif (preg_match('/(@lunch)/i', $lastMessage)) {
                $status = "lunch";
            } elseif (preg_match('/(@?back)/i', $lastMessage)) {
                $status = "in";
            }
            $statuses[] = [
                $this->users[$userId]->real_name => [
                    'status' => $status,
                    'since' => Carbon::createFromTimestampUTC($lastMessageTs)->diffForHumans(),
                    'last_message' => $lastMessage,
                ]
            ];
        }

        return compact('statuses', 'usersMessages');
    }
}
