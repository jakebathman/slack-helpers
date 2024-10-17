<?php

namespace App\Http\Controllers;

use App\Exceptions\SlackApiException;
use App\SlackClient;
use App\SlackUser;
use App\StatusMatcher;
use App\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GetStaffIn extends Controller
{
    public $statuses = [];

    protected $channelId;
    protected $client;
    protected $teamId;

    public function __invoke(Request $request, $teamId = null)
    {
        $this->prepare($teamId);

        return $this->getStatuses();
    }

    public function prepare($teamId = null)
    {
        Log::debug(microtime(true) . ' ' . __CLASS__ . '@' . __FUNCTION__ . '#' . __LINE__);
        $this->teamId = $teamId ?? config('services.slack.team_id');
        $workspace = Token::where('team_id', $this->teamId)->first();

        $this->channelId = $workspace->getGeneralChannelId();

        $this->client = new SlackClient($this->teamId);
        $this->users = $this->client->getUsers();

        return $this;
    }

    public function getStatuses()
    {
        try {
            Log::debug(microtime(true) . ' Fetching messages from API');
            $messages = $this->client->getMessagesFromToday($this->channelId)
                ->unique('client_msg_id');
            Log::debug(microtime(true) . ' Fetching messages from API Done');
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
            // Filters out non-users (e.g. bots)
            return isset($message['user']);
        })
            ->mapToGroups(function ($message) {
                return [
                    $message['user'] => [
                        'text' => $message['text'],
                        'ts' => $message['ts'],
                    ],
                ];
            });

        Log::debug(microtime(true) . ' Updating all user info');

        foreach ($usersMessages as $userId => $messages) {
            // Update and cache the user's info from Slack
            $this->updateUserInfo($userId);

            // Determine status
            $user = $this->users[$userId];

            $statusInfo = $this->getUserStatus($user, $messages);

            if ($statusInfo) {
                $this->statuses[$userId] = $statusInfo;
            }
        }
        Log::debug(microtime(true) . ' Updating all user info done');

        return [
            'status' => 'success',
            'meta' => [
                'user_status_count' => count($this->statuses),
            ],
            'data' => [
                'statuses' => $this->statuses,
                'messages' => $usersMessages,
            ],
        ];
    }

    public function updateUserInfo($userId)
    {
        Log::debug(microtime(true) . " Updating user info {$userId}");

        if ($this->users->has($userId)) {
            // This is a known user, but see if their info is out of date
            $user = $this->users->get($userId);
            $userInfoUpdatedAt = $user->updated_at;

            if (Carbon::parse($userInfoUpdatedAt)->diffInDays() < 30) {
                // Nothing needs updating
                Log::debug(microtime(true) . " Updating user info {$userId} Done (cache)");
                return;
            }
        }

        // Need to pull and save this user's info from Slack
        $userInfo = $this->client->getUserInfo($userId);

        $displayName = empty($userInfo['profile']['display_name']) ? $userInfo['name'] : $userInfo['profile']['display_name'];

        $user = SlackUser::updateOrCreate(
            [
                'slack_id' => $userInfo['id'],
            ],
            [
                'team_id' => $userInfo['team_id'],
                'display_name' => $displayName,
                'color' => $userInfo['color'],
                'real_name' => $userInfo['real_name'],
                'tz' => $userInfo['tz'],
                'updated' => $userInfo['updated'],
            ]
        );

        $this->users->put($user->slack_id, $user);
        Log::debug(microtime(true) . " Updating user info {$userId} Done");
    }

    public function getUserStatus($user, $messages)
    {
        // Work backwards through messages (starting with most recent)
        // so that any expired @break messages are skipped
        foreach ($this->prepareMessages($messages) as $message) {
            $lastMessage = Arr::get($message, 'text');
            $lastMessageTs = Arr::get($message, 'ts');

            if (StatusMatcher::hasIn($lastMessage)) {
                $status = 'in';
            } elseif (StatusMatcher::hasBreak($lastMessage)) {
                // If it's been less than XX minutes, they're on break
                $timeSinceMessage = time() - $lastMessageTs;

                // Most breaks are 20 min, except walks which are 30
                $breakDuration = 20;
                if (preg_match('/[@!\+](walk)/i', $lastMessage)) {
                    $breakDuration = 30;
                }

                if ($timeSinceMessage > ($breakDuration * 60)) {
                    // Skip this message, go to the next one
                    continue;
                } else {
                    $status = 'break';
                }
            } elseif (StatusMatcher::hasOut($lastMessage)) {
                $status = 'out';
            } elseif (StatusMatcher::hasLunch($lastMessage)) {
                $status = 'lunch';
            } elseif (StatusMatcher::hasBack($lastMessage)) {
                $status = 'in';
            }

            // Stop processing earlier messages, since this one set the status for the user
            return [
                'slack_id' => $user->slack_id,
                'display_name' => $user->display_name,
                'real_name' => $user->real_name,
                'status' => $status,
                'since' => Carbon::createFromTimestampUTC($lastMessageTs)->diffForHumans(),
                'last_message' => $lastMessage,
                'team_id' => $user->team_id,
                'tz' => $user->tz,
            ];
        }
    }

    public function prepareMessages($messages)
    {
        return collect($messages)->filter(function ($message) {
            $pattern = StatusMatcher::mergedPattern();

            return preg_match($pattern, $message['text']);
        })
            ->sortByDesc('ts')
            ->values();
    }

    /**
     * Some @status messages might be using a user group
     * instead of plain text. We want to just use that plain
     * text version of the message so we need to parse out
     * some of the advanced syntax.
     *
     * More info: https://api.slack.com/reference/surfaces/formatting#advanced
     */
    public function parseSpecialMentionsToText($text)
    {
        // Parse out subteam mentions
        preg_match(StatusMatcher::PREG_SUBTEAM_MENTION, $text, $subteamMatches);

        if ($subteamMatches[2] ?? false) {
            // If the mention has a label, swap that in
            $text = preg_replace(StatusMatcher::PREG_SUBTEAM_MENTION, '$2', $text);
        } elseif ($subteamMatches[1] ?? false) {
            // Some subteam mentions don't have a label,
            // so we have to check the subteam mention ID
            $text = preg_replace(StatusMatcher::PREG_SUBTEAM_MENTION, '$1', $text);
        }

        // Parse out subteam mentions
        $text = preg_replace(StatusMatcher::PREG_SUBTEAM_MENTION, '$1', $text);

        // Parse out special mentions
        $text = preg_replace(StatusMatcher::PREG_SPECIAL_MENTION, '@$1', $text);

        return $text;
    }
}
