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
    const PREG_IN = '([@!\+](in|ingrid|​ingrid|innie|iinne)([^\w]|$)|^in$)';
    const PREG_BREAK = '([@!\+](brb|break|relo)([^\w]|$)|^brb$|^:(coffee|latte):$|^:tea:(\s*?:timer_clock:)?$)';
    const PREG_LUNCH = '([@!\+](lunch(ito)?|brunch|lunching|snack(ing)?)([^\w]|$)|^lunch( time)?$)';
    const PREG_BACK = '([@!\+]back([^\w]|$)|^back$)';
    const PREG_OUT = '([@!\+](out|ofnbl|ofn|oot|notin|vote|voting|therapy|errands?)([^\w]|$)|^out$)';
    const PREG_SUBTEAM_MENTION = '/\<\!subteam\^(?:[A-Z0-9]+)(?:\|(.*?))?\>/i';
    const PREG_SPECIAL_MENTION = '/\<\!(here|channel|everyone)\>/i';

    public $statuses = [];

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
        $usersMessages = $messages->filter(function($message) {
            // Filters out non-users (e.g. bots)
            return isset($message->user);
        })
        ->mapToGroups(function ($message) {
            return [
                $message->user => [
                    'text' => $message->text,
                    'ts' => $message->ts,
                ],
            ];
        });

        foreach ($usersMessages as $userId => $messages) {

            // Update and cache the user's info from Slack
            $this->updateUserInfo($userId);

            // Determine status
            $user = $this->users[$userId];

            $statusInfo = $this->getUserStatus($user, $messages);

            if($statusInfo){
                $this->statuses[$userId] = $statusInfo;
            }
        }

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
        if ($this->users->has($userId)) {
            // This is a known user, but see if their info is out of date
            $user = $this->users->get($userId);
            $userInfoUpdatedAt = $user->updated_at;

            if (Carbon::parse($userInfoUpdatedAt)->diffInDays() > 30) {
                // Nothing needs updating
                return;
            }
        }

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

    public function getUserStatus($user, $messages)
    {
        // Work backwards through messages (starting with most recent)
        // so that any expired @break messages are skipped
        foreach ($this->prepareMessages($messages) as $message) {
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
            $pattern = self::pregPattern(
                self::PREG_IN,
                self::PREG_BREAK,
                self::PREG_OUT,
                self::PREG_LUNCH,
                self::PREG_BACK
            );

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
        $text = preg_replace(self::PREG_SUBTEAM_MENTION, '$1', $text);

        // Parse out special mentions
        $text = preg_replace(self::PREG_SPECIAL_MENTION, '@$1', $text);

        return $text;
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
