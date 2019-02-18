<?php

namespace App\Http\Controllers;

use App\SlackClient;
use App\SlackUser;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class SlashIsIn extends Controller
{

    public function __construct($teamId = null)
    {
        $this->channelId = config('services.slack.general_channel_id');
        $this->teamId = $teamId ?? config('services.slack.team_id');
        $this->client = new SlackClient($this->teamId);
        $this->users = $this->client->getUsers();
    }

    public function __invoke(Request $request)
    {
        $message = $request->get('text');
        $userId = $request->get('user_id');

        // Figure out who was @mentioned in the slash command
        // Slack escapes @mentions to look like <@U012ABCDEF>
        $pattern = "/\<@([\A-Z0-9]+)(?:\|[\w]+)?\>/";
        if (! preg_match_all($pattern, $message, $mentions)) {
            return "Make sure you're including a username, like */IsIn @someone*";
        }

        if (count($mentions[1]) > 1) {
            return "Only mention one person, so I know who you're looking for! E.g. */IsIn @someone*";
        }

        $userMentionedId = $mentions[1][0];

        if ($userId == $userMentionedId) {
            return Arr::random([
                "Hey, wait a sec... You can't fool me! :robot_face:",
                "I would think you'd know if you're in or out :thinking_face:",
                "Maybe try tagging someone _else_ next time? :upside_down_face:",
            ]);
        }

        // Get the list of who's in
        $statusData = (new GetStaffIn())->getStatuses();

        if (array_get($statusData, 'status') != 'success') {
            return "Sorry, something went wrong trying to look that up. Here's the error message:\n> " . array_get($statusData, 'message', '(No error message)');
        }

        $statuses = collect(array_get($statusData, 'data.statuses'));

        // Get the status of the mentioned person
        if ($info = $statuses->get($userMentionedId)) {
            return "@{$info['display_name']} is *@{$info['status']}*. Their last message in #general was {$info['since']}:\n> {$info['last_message']}";
            $statuses->get($userMentionedId);
        }

        return "I've not seen @{$info['display_name']} in #general yet today, so you can assume they're *@out* right now.";
    }
}
