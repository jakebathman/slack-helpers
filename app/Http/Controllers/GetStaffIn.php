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
    const PREG_IN = '([@!\+](in|ingrid|​ingrid|innie)([^\w]|$)|^in$)';
    const PREG_BREAK = '([@!\+](brb|break|relo)([^\w]|$)|^brb$|^:(coffee|latte):$|^:tea:(\s*?:timer_clock:)?$)';
    const PREG_LUNCH = '([@!\+](lunch|brunch)([^\w]|$)|^lunch( time)?$)';
    const PREG_BACK = '([@!\+]back([^\w]|$)|^back$)';
    const PREG_OUT = '([@!\+](out|ofnbl|ofn|oot|notin|vote|voting)([^\w]|$)|^out$)';

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
                return false;
            }

            $pattern = self::pregPattern(
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

                if (self::hasIn($lastMessage)) {
                    $status = 'in';
                } elseif (self::hasBreak($lastMessage)) {
                    // If it's been less than 20 minutes, they're on break
                    $timeSinceMessage = time() - $lastMessageTs;
                    if ($timeSinceMessage > (20 * 60)) {
                        // Skip this message, go to the next one
                        continue;
                    } else {
                        $status = 'break';
                    }
                } elseif (self::hasOut($lastMessage)) {
                    $status = 'out';
                } elseif (self::hasLunch($lastMessage)) {
                    $status = 'lunch';
                } elseif (self::hasBack($lastMessage)) {
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

    public static function hasIn($text)
    {
        return preg_match(self::pregPattern(self::PREG_IN), $text);
    }

    public static function hasBreak($text)
    {
        return preg_match(self::pregPattern(self::PREG_BREAK), $text);
    }

    public static function hasLunch($text)
    {
        return preg_match(self::pregPattern(self::PREG_LUNCH), $text);
    }

    public static function hasBack($text)
    {
        return preg_match(self::pregPattern(self::PREG_BACK), $text);
    }

    public static function hasOut($text)
    {
        return preg_match(self::pregPattern(self::PREG_OUT), $text);
    }

    public static function pregPattern(...$patterns)
    {
        return '/(' . implode('|', $patterns) . ')/i';
    }
}
