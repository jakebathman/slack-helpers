<?php

namespace App\Http\Controllers;

use App\Exceptions\SlackApiException;
use App\SlackClient;
use App\SlackUser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class GetStaffIn extends Controller
{
    const PREG_IN = '(@in|^in$)';
    const PREG_BREAK = '(@brb|@break|^brb$|^:coffee:$|^:tea:(\s*?:timer_clock:)?$)';
    const PREG_LUNCH = '(@lunch|^lunch$)';
    const PREG_BACK = '(^@?back$)';
    const PREG_OUT = '(@out|^out$|@ofnbl)';

    protected $channelId;
    protected $client;
    protected $teamId;

    public function prepare($teamId = null)
    {
        $this->teamId = $teamId ?? config('services.slack.team_id');
        $this->channelId = config('services.slack.general_channel_id');
        $this->client = new SlackClient($this->teamId);
        $this->users = $this->client->getUsers();

        return $this;
    }

    public function __invoke(Request $request, $teamId = null)
    {
        $this->prepare($teamId);

        return $this->getStatuses();
    }

    public function getStatuses()
    {
        try {
            $messages = $this->client->getMessagesFromToday($this->channelId)
                ->unique('client_msg_id');
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
            if (! isset($message->user)) {
                // Filters out non-users (e.g. bots)
                dump('non-user');
                return false;
            }

            $pattern = $this->pregPattern(
                self::PREG_IN,
                self::PREG_BREAK,
                self::PREG_OUT,
                self::PREG_LUNCH,
                self::PREG_BACK
            );
            return preg_match($pattern, $message->text);
        })
            ->mapToGroups(function ($message) {
                return [
                    $message->user => [
                        'text' => $message->text,
                        'ts' => $message->ts,
                    ],
                ];
            });

            $statuses = [];
        foreach ($usersMessages as $userId => $messages) {
            if ($this->userInfoNeedsUpdating($userId)) {
                // Need to pull and save this user's info from Slack
                dump('updating user ' . $userId);
                $userInfo = SlackUserClient::info($userId)->user;

                $user = SlackUser::updateOrCreate(
                    [
                        'slack_id' => $userInfo->id,
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
            // Work backwards through messages (starting with most recent)
            // so that any expired @break messages are skipped
            foreach ($messages as $message) {
                $lastMessage = Arr::get($message, 'text');
                $lastMessageTs = Arr::get($message, 'ts');

                if ($this->hasIn($lastMessage)) {
                    $status = 'in';
                } elseif ($this->hasBreak($lastMessage)) {
                    // If it's been less than 20 minutes, they're on break
                    $timeSinceMessage = time() - $lastMessageTs;
                    if ($timeSinceMessage > (20 * 60)) {
                        // Skip this message, go to the next one
                        continue;
                    } else {
                        $status = 'break';
                    }
                } elseif ($this->hasOut($lastMessage)) {
                    $status = 'out';
                } elseif ($this->hasLunch($lastMessage)) {
                    $status = 'lunch';
                } elseif ($this->hasBack($lastMessage)) {
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

                // Stop processing earlier messages, since this one set the status for the user
                break;
            }
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

    public function hasIn($text)
    {
        return preg_match($this->pregPattern(self::PREG_IN), $text);
    }

    public function hasBreak($text)
    {
        return preg_match($this->pregPattern(self::PREG_BREAK), $text);
    }

    public function hasLunch($text)
    {
        return preg_match($this->pregPattern(self::PREG_LUNCH), $text);
    }

    public function hasBack($text)
    {
        return preg_match($this->pregPattern(self::PREG_BACK), $text);
    }

    public function hasOut($text)
    {
        return preg_match($this->pregPattern(self::PREG_OUT), $text);
    }

    protected function pregPattern(...$patterns)
    {
        return '/(' . implode('|', $patterns) . ')/i';
    }
}
