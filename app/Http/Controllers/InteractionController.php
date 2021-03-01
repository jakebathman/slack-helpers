<?php

namespace App\Http\Controllers;

use App\Events\Slack\CreateEntryInteraction;
use App\Http\Controllers\Controller;
use App\Slack\HandleInteraction;
use App\Token;
use App\User;
use Illuminate\Support\Arr;

class InteractionController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Interactions in the Slack API are fired any time a user interacts with
     * some bit of UI. These include built-in action buttons (such as message
     * or global actions) and ones that this application presents to the user
     * (such as a modal input with a submit button).
     *
     * All of these interaction payloads are structured similarly, and this
     * controller is then main ingress point for handling them.
     *
     * More info on interactivity: https://api.slack.com/messaging/interactivity
     */
    public function __invoke()
    {
        $data = json_decode(request('payload'), true);

        switch (Arr::get($data, 'type')) {
            case 'block_actions':
                // The user clicked an action button, such as "Close Results"
                $this->handleBlockAction($data);
                break;

            default:
                # code...
                break;
        }
    }

    public function handleBlockAction($data)
    {
        $responseUrl = Arr::get($data, 'response_url');

        switch (Arr::get($data, 'actions.0.action_id')) {
            case 'close_search_results':
                return HandleInteraction::deleteOriginal($responseUrl);
                break;

            default:
                # code...
                break;
        }
    }
}
