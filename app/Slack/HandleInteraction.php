<?php

namespace App\Slack;

use Illuminate\Support\Facades\Http;

class HandleInteraction
{
    public static function deleteOriginal($responseUrl)
    {
        // POST to this URL with only the delete_original payload
        // More info: https://api.slack.com/interactivity/handling#async_responses

        return Http::post($responseUrl, [
            'delete_original' => true,
        ]);
    }
}
