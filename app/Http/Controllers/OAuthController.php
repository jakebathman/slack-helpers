<?php

namespace App\Http\Controllers;

use App\Token;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function redirect()
    {
        $code = request()->get('code', null);

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

        if ($error = array_get($data, 'error')) {
            return $error;
        }

        Token::updateOrCreate(
            [ 'team_id' => array_get($data, 'team_id') ],
            [
                'access_token' => array_get($data, 'access_token'),
                'scope' => array_get($data, 'scope'),
                'user_id' => array_get($data, 'user_id'),
                'team_name' => array_get($data, 'team_name'),
                'incoming_webhook_url' => array_get($data, 'incoming_webhook.url'),
                'incoming_webhook_channel' => array_get($data, 'incoming_webhook.channel'),
                'incoming_webhook_channel_id' => array_get($data, 'incoming_webhook.channel_id'),
                'incoming_webhook_configuration_url' => array_get($data, 'incoming_webhook.configuration_url'),
                'bot_user_id' => array_get($data, 'bot.bot_user_id'),
                'bot_access_token' => array_get($data, 'bot.bot_access_token'),
            ]
        );

        return redirect()->to('/')->with('status', "Success! You've installed @IsInBot and you're good to go.");
    }
}
