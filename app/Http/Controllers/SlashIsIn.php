<?php

namespace App\Http\Controllers;

use App\SlackClient;
use App\Slack\BlockKitMessage;
use App\Token;
use App\UserChecker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SlashIsIn extends Controller
{
    protected $replyMessage;

    public function __invoke($teamId = null)
    {
        Log::debug(__CLASS__ . '@' . __FUNCTION__ . '#' . __LINE__);
        $this->teamId = $teamId ?? request('team_id');
        $workspace = Token::where('team_id', $this->teamId)->first();

        Log::debug(microtime(true) . ' Getting workspace channel ID');
        $this->channelId = $workspace->getGeneralChannelId();
        Log::debug($this->channelId);
        $this->client = new SlackClient($this->teamId);
        $userId = request('user_id');

        Log::debug(microtime(true) . ' Getting user info');
        // Check if this user is allowed
        $userInfo = $this->client->getUserInfo($userId);
        Log::debug(microtime(true) . ' Getting user info Done');

        if (! UserChecker::callingUserAllowed($userInfo)) {
            return 'Sorry, this command is not available to Single Channel Guests.';
        }

        $this->replyMessage = new BlockKitMessage;

        $message = request('text');

        // A user can send "help" to get usage instructions
        if (strtolower($message) == 'help') {
            Log::debug(microtime(true) . ' Replying with help message');
            return $this->helpReply();
        }

        // Figure out who was @mentioned in the slash command
        // Slack escapes @mentions to look like <@U012ABCDEF>
        $pattern = '/\<@([A-Z0-9]+)(?:\|[\w\W]+?)?\>/';
        preg_match_all($pattern, $message, $mentions);
        Log::debug('Checking for mentions', ['message' => $message, 'mentions' => $mentions]);

        if (count($mentions[1]) > 1) {
            return $this->reply("Only mention one person, so I know who you're looking for! E.g. */IsIn @someone*");
        }

        Log::debug(microtime(true) . ' Getting statuses');
        // Get the list of who's in
        $statusData = (new GetStaffIn)
            ->prepare($this->teamId)
            ->getStatuses();
        Log::debug(microtime(true) . ' Getting statuses Done');

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
            return $this->reply("@{$info['display_name']} is *@{$info['status']}*. Their last message in #isin-wfriends was {$info['since']}:\n> {$info['last_message']}");
        }

        Log::debug(microtime(true) . ' Getting info on mentioned user');
        // Get the user's info
        $userInfo = $this->client->getUserInfo($mentions[1][0]);
        Log::debug(microtime(true) . ' Getting info on mentioned user Done');

        $displayName = strlen($userInfo['profile']['display_name']) > 0 ? $userInfo['profile']['display_name'] : $userInfo['name'];

        return $this->reply("I've not seen @{$displayName} in #isin-wfriends yet today, so you can assume they're *@out* right now.");
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
                    ],
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

    protected function helpReply()
    {
        $this->replyMessage->blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Hey there 👋 I'm IsInBot, but you can call me Izzy.\n\nI help everyone at Tighten see who's at work and available. I can't know your status automatically, so here's how you tell me your status and quickly see if someone else is in.",
                ],
            ],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Status options',
                    'emoji' => true,
                ],
                'level' => 3,
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'There are four statuses that someone can have, based on the messages they send in our status channel #isin-wfriends:',
                ],
            ],
            [
                'type' => 'table',
                'column_settings' => [
                    ['is_wrapped' => true],
                    ['is_wrapped' => true],
                    ['is_wrapped' => true],
                    ['is_wrapped' => true],
                ],
                'rows' => [
                    [
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Status', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => "This means you're...", 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Aliases', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Notes', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'in', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Here and working'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => ' '],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => ' '],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'break', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Taking a short break away from Slack (coffee refill, walking the dog, etc.)'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'brb '],
                                        ['type' => 'emoji', 'name' => 'tea'],
                                        ['type' => 'text', 'text' => ' '],
                                        ['type' => 'emoji', 'name' => 'coffee'],
                                        ['type' => 'text', 'text' => ' '],
                                        ['type' => 'emoji', 'name' => 'diet-coke'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'If you use '],
                                        ['type' => 'text', 'text' => '@break', 'style' => ['code' => true]],
                                        ['type' => 'text', 'text' => ", I'll automatically mark you back in after ~20 minutes, so no need to tell me you're back (unless you want to say "],
                                        ['type' => 'text', 'text' => '@back', 'style' => ['code' => true]],
                                        ['type' => 'text', 'text' => ', I always love hearing from you)'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'lunch', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'On a meal break'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'dinner, brunch, breakfast'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => ' '],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'out', 'style' => ['bold' => true]],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'Away from Slack for the rest of the day or just a long while'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => 'ofn, ofnbl, errands'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'rich_text',
                            'elements' => [
                                [
                                    'type' => 'rich_text_section',
                                    'elements' => [
                                        ['type' => 'text', 'text' => "You'll often see "],
                                        ['type' => 'text', 'text' => '@ofn', 'style' => ['code' => true]],
                                        ['type' => 'text', 'text' => ' (out for now) or similar, which adds a bit of context for everyone else'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'I support using `@` `!` `$` `+` or no prefix at all with these basic words, so use whatever you\'re comfortable with. `@in`, `!in`, `$in`, and `+in` all look the same to me.',
                ],
            ],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Note your own status throughout the day',
                    'emoji' => true,
                ],
                'level' => 3,
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Send a message in #isin-wfriends with your current status. Most folks use *@in*, *@out*, *@lunch*, and *@break*.',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'If you use a prefix character (`@` `!` `$` `+`) you can put your status anywhere in your message.',
                ],
            ],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => ':white_check_mark: Valid status updates',
                    'emoji' => true,
                ],
                'level' => 4,
            ],
            ['type' => 'markdown', 'text' => '> "Headed to @lunch at Chipotle!"'],
            ['type' => 'markdown', 'text' => '> "@out til later"'],
            ['type' => 'markdown', 'text' => '> "in"'],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => ':x: Invalid status updates',
                    'emoji' => true,
                ],
                'level' => 4,
            ],
            ['type' => 'markdown', 'text' => '> "Headed to lunch"'],
            ['type' => 'markdown', 'text' => '> "grabbing more coffee"'],
            ['type' => 'markdown', 'text' => '> "hello everyone"'],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Ask about someone else',
                    'emoji' => true,
                ],
                'level' => 3,
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "The point of updating your status isn't to keep tabs on you, but to let others know if you're in and available.",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Use the `/isin` slash command in any channel in this workspace to see who's in, taking a break, at lunch, or out.",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "By default, you'll get a list of everyone who's updated their status today. But you can also add someone's @mention to get just their status:",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '> `/isin @jakebathman`',
                ],
            ],
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'FAQs',
                    'emoji' => true,
                ],
                'level' => 3,
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*How are my status updates used? Does this log my hours and keep tabs on me?*',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "The app stores no messages/statuses, update times, etc. This is simply a tool for colleagues to see who's in the office in an all-remote workspace. Each time /isin is used, Izzy parses the Slack message history for the day, so everything's always fresh.",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*What if I send the wrong thing? How do you sort through it all?*',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Your most recent message in #isin-wfriends determines your current status. You can edit a message and the edit takes effect immediately.',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Who made this?*',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Jake Bathman, who you can DM here in Slack with any bugs or suggestions.',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Can I see the source code?*',
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => ':github: https://github.com/jakebathman/slack-helpers',
                ],
            ],
            ['type' => 'divider'],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => '❓View this message again at any time with `/isin help`',
                    ],
                ],
            ],
        ];

        Log::debug(microtime(true) . (string)$this->replyMessage);
        // Return the message with a Close button
        return response()->json(
            $this->replyMessage
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
