<?php

namespace App\Http\Controllers;

use App\Slack\BlockKitMessage;
use App\SlackClient;
use App\Token;
use App\UserChecker;
use Illuminate\Support\Arr;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class SlashIsIn extends Controller
{
    protected $replyMessage;

    public function __invoke($teamId = null)
    {
        $this->teamId = $teamId ?? request('team_id');
        $workspace = Token::where('team_id', $this->teamId)->first();

        $this->channelId = $workspace->getGeneralChannelId();
        $this->client = new SlackClient($this->teamId);
        $userId = request('user_id');

        // Check if this user is allowed
        $userInfo = $this->client->getUserInfo($userId);

        if (! UserChecker::callingUserAllowed($userInfo)) {
            return 'Sorry, this command is not available to Single Channel Guests.';
        }

        $this->replyMessage = new BlockKitMessage;

        $message = request('text');

        // Figure out who was @mentioned in the slash command
        // Slack escapes @mentions to look like <@U012ABCDEF>
        $pattern = '/\<@([A-Z0-9]+)(?:\|[\w]+)?\>/';
        preg_match_all($pattern, $message, $mentions);

        if (count($mentions[1]) > 1) {
            return $this->reply("Only mention one person, so I know who you're looking for! E.g. */IsIn @someone*");
        }

        // Get the list of who's in
        $statusData = (new GetStaffIn)
            ->prepare($this->teamId)
            ->getStatuses();

        if (Arr::get($statusData, 'status') != 'success') {
            return $this->reply(
                "Sorry, something went wrong trying to look that up. Here's the error message:\n> " . Arr::get($statusData, 'message', '(No error message)')
            );
        }

        $statuses = collect(Arr::get($statusData, 'data.statuses'));

        if (empty($mentions[0])) {
            // If no one was @mentioned, return all users that are @in (and specify those on break)
            $statusGroups = $statuses->groupBy('status');

            $text = [];
            $groups = [
                'in' => ':wave:',
                'break' => ':coffee:',
                'lunch' => ':bento:',
                'out' => ':v:',
            ];

            foreach ($groups as $group => $emoji) {
                if (! $statusGroups->has($group)) {
                    continue;
                }

                $text[] = "*{$emoji} {$group}*";
                $text[] = $statusGroups[$group]->map(function ($status) use ($emoji) {
                        return "@{$status['display_name']}";
                })
                ->sort()
                ->implode("\n");

                $text[] = '';
            }

            if (count($text) == 0) {
                return $this->reply('Sorry, no one is @in right now :shrug:');
            }

            return $this->reply(implode("\n", $text));
        }

        // Get the status of the mentioned person
        if ($info = $statuses->get($mentions[1][0])) {
            return $this->reply("@{$info['display_name']} is *@{$info['status']}*. Their last message in #general was {$info['since']}:\n> {$info['last_message']}");
        }

        // Get the user's info
        $userInfo = SlackUserClient::info($mentions[1][0])->user;

        $displayName = strlen($userInfo->profile->display_name) > 0 ? $userInfo->profile->display_name : $userInfo->name;

        return $this->reply("I've not seen @{$displayName} in #general yet today, so you can assume they're *@out* right now.");
    }

    protected function reply($text)
    {
        return response()->json(
            $this->replyMessage
                ->block([
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                        'verbatim' => true,
                    ]
                ])
                ->actions([
                    [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'Close',
                        ],
                        'action_id' => 'close_results',
                    ],
            ])
        );
    }
}
