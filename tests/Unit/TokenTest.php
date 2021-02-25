<?php

namespace Tests\Feature;

use App\Factories\SlackApiUserFactory;
use App\Http\Controllers\SlashIsIn;
use App\Token;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function it_returns_channel_id_from_database()
    {
        $teamId = 'T0250LTFC';
        $generalChannelId = 'ABCD1234';

        factory(Token::class)->create([
            'team_id' => $teamId,
            'general_channel_id' => $generalChannelId,
        ]);

        $token = Token::first();

        $this->assertEquals($generalChannelId, $token->getGeneralChannelId());
    }

    /** @test */
    function it_updates_model_with_channel_id_from_api()
    {
        $teamId = 'T0250LTFC';
        $generalChannelId = 'ABCD1234';

        factory(Token::class)->create([
            'team_id' => $teamId,
        ]);

        $token = Token::first();

        Http::fake([
            'slack.com/api/conversations.list' => Http::response([
                'ok' => true,
                'channels' => [
                    [
                        'id' => 'ZYXW321',
                        'name' => 'foo',
                    ],
                    [
                        'id' => $generalChannelId,
                        'name' => 'general',
                    ],
                    [
                        'id' => '1234ABCD',
                        'name' => 'bar',
                    ],
                ],
            ]),
        ]);

        $this->assertEquals($generalChannelId, $token->getGeneralChannelId());
    }
}
