<?php

namespace App\Http\Controllers;

use App\Token;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class OAuthController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function redirect()
    {
        $code = request('code', null);

        // Request a token using this code
        $response = $this->client->post('https://slack.com/api/oauth.access', [
            'form_params' => [
                'client_id' => config('services.slack.client_id'),
                'client_secret' => config('services.slack.client_secret'),
                'code' => $code,
                'redirect_uri' => route('oauth.redirect'),
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if ($error = Arr::get($data, 'error')) {
            return $error;
        }

        Token::updateOrCreate(
            [ 'team_id' => Arr::get($data, 'team_id') ],
            [
                'access_token' => Arr::get($data, 'access_token'),
                'scope' => Arr::get($data, 'scope'),
                'user_id' => Arr::get($data, 'user_id'),
                'team_name' => Arr::get($data, 'team_name'),
                'incoming_webhook_url' => Arr::get($data, 'incoming_webhook.url'),
                'incoming_webhook_channel' => Arr::get($data, 'incoming_webhook.channel'),
                'incoming_webhook_channel_id' => Arr::get($data, 'incoming_webhook.channel_id'),
                'incoming_webhook_configuration_url' => Arr::get($data, 'incoming_webhook.configuration_url'),
                'bot_user_id' => Arr::get($data, 'bot.bot_user_id'),
                'bot_access_token' => Arr::get($data, 'bot.bot_access_token'),
            ]
        );

        return redirect()->to('/')->with('status', "Success! You've installed @IsInBot and you're good to go.");
    }
}
