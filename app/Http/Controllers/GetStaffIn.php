<?php

namespace App\Http\Controllers;

use DateTime;
use App\SlackUser;
use App\SlackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;
use App\Exceptions\SlackApiException;

class GetStaffIn extends Controller
{
    protected $channelId;
    protected $client;
    protected $teamId;

    public function __construct($teamId = null)
    {
        $this->channelId = config('services.slack.general_channel_id');
        $this->teamId = $teamId ?? config('services.slack.team_id');
        $this->client = new SlackClient($this->teamId);
        $this->users = $this->client->getUsers();
    }

    public function __invoke(Request $request)
    {
        return $this->getStatuses();
    }

    public function getStatuses()
    {
        try {
            $messages = $this->client->getMessagesFromToday($this->channelId);
        } catch (SlackApiException $e) {
            report($e);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => [
                    'channel_id' => $this->channelId,
                    'team_id' => $this->teamId,
                    'exception_trace' => $e->getTrace(),
                ],
            ];
        }

        // Group messages by user and filter only @in/@brb/@out/@lunch/@back
        $usersMessages = $messages->filter(function ($message) {
            return preg_match('/(@in|@brb|^brb$|^:coffee:$|^:tea:(\s*?:timer_clock:)?$|@out|@lunch|@?back)/i', $message->text);
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
            if ($this->userInfoNeedsUpdating($userId)) {
                // Need to pull and save this user's info from Slack
                $userInfo = SlackUserClient::info($userId)->user;

                $user = SlackUser::updateOrCreate(
                    [
                        'slack_id' => $userInfo->id
                    ],
                    [
                        'team_id' => $userInfo->team_id,
                        'display_name' => $userInfo->profile->display_name,
                        'color' => $userInfo->color,
                        'real_name' => $userInfo->real_name,
                        'tz' => $userInfo->tz,
                        'updated' => $userInfo->updated,
                    ]
                );

                $this->users->put($user->slack_id, $user);
            }

            // Determine status
            $lastMessage = array_get($messages->first(), 'text');
            $lastMessageTs = array_get($messages->first(), 'ts');

            if (preg_match('/(@in|^in$)/i', $lastMessage)) {
                $status = 'in';
            } elseif (preg_match('/(@brb|^brb$|^:coffee:$|^:tea:(\s*?:timer_clock:)?$)/i', $lastMessage)) {
                // If it's been less than 20 minutes, they're on break
                $timeSinceMessage = time() - $lastMessageTs;
                if ($timeSinceMessage > (20 * 60)) {
                    $status = 'in';
                } else {
                    $status = 'break';
                }
            } elseif (preg_match('/(@out|^out$)/i', $lastMessage)) {
                $status = 'out';
            } elseif (preg_match('/(@lunch|^lunch$)/i', $lastMessage)) {
                $status = 'lunch';
            } elseif (preg_match('/(@?back)/i', $lastMessage)) {
                $status = 'in';
            }

            $thisUser = $this->users[$userId];
            $statuses[$thisUser->slack_id] = [
                'slack_id' => $thisUser->slack_id,
                'display_name' => $thisUser->display_name,
                'real_name' => $thisUser->real_name,
                'status' => $status,
                'since' => Carbon::createFromTimestampUTC($lastMessageTs)->diffForHumans(),
                'last_message' => $lastMessage,
                'team_id' => $thisUser->team_id,
                'tz' => $thisUser->tz,
            ];
        }

        return [
            'status' => 'success',
            'meta' => [
                'user_status_count' => count($statuses),
            ],
            'data' => [
                'statuses' => $statuses,
                'messages' => $usersMessages,
            ],
        ];
    }

    public function userInfoNeedsUpdating($userId)
    {
        if (! $this->users->has($userId)) {
            return true;
        }

        $user = $this->users->get($userId);
        $userInfoUpdatedAt = $user->updated_at;

        if (Carbon::parse($userInfoUpdatedAt)->diffInDays() > 30) {
            return true;
        }
    }
}
