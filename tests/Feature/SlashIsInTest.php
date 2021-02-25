<?php

namespace Tests\Feature;

use App\Factories\SlackApiUserFactory;
use App\Http\Controllers\SlashIsIn;
use App\Token;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlashIsInTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function it_does_not_allow_single_channel_guest_users()
    {
        factory(Token::class)->create([
            'team_id' => 'T0250LTFC',
            'general_channel_id' => 'ABCD1234',
        ]);

        Http::fake([
            'slack.com/api/users.info' => Http::response([
                'user' => [
                    'id' => 'U8RVBDGAJ',
                    'team_id' => 'T0250LTFC',
                    'is_restricted' => true,
                    'is_ultra_restricted' => true,
                ],
            ]),
        ]);

        $payload = [
            'team_id' => 'T0250LTFC',
            'user_id' => 'U8RVBDGAJ',
            'user_name' => 'jake',
            'command' => '/isin',
            'text' => null,
        ];

        $response = $this->post(route('slash.isin'), $payload);
        $response->assertSeeText('Sorry, this command is not available to Single Channel Guests.');
    }

    /** @test */
    function it_allows_full_workspace_users()
    {
        factory(Token::class)->create([
            'team_id' => 'T0250LTFC',
            'general_channel_id' => 'ABCD1234',
        ]);

        Http::fake([
            'slack.com/api/users.info' => Http::response([
                'user' => [
                    'id' => 'U8RVBDGAJ',
                    'team_id' => 'T0250LTFC',
                    'is_restricted' => false,
                    'is_ultra_restricted' => false,
                ],
            ]),

            'slack.com/api/conversations.history' => Http::response([
                'ok' => true,
                'messages' => [],
            ]),
        ]);

        $payload = [
            'team_id' => 'T0250LTFC',
            'user_id' => 'U8RVBDGAJ',
            'user_name' => 'jake',
            'command' => '/isin',
            'text' => null,
        ];

        $response = $this->post(route('slash.isin'), $payload);
        $response->assertDontSeeText('Sorry, this command is not available to Single Channel Guests.');
    }

    /** @test */
    function it_determines_single_channel_guest_users_correctly()
    {
        $user = app(SlackApiUserFactory::class)
            ->isRestricted()
            ->isUltraRestricted()
            ->create();

        $this->assertTrue(SlashIsIn::isSingleChannelGuest($user));

        $this->assertFalse(SlashIsIn::isMultiChannelGuest($user));
        $this->assertFalse(SlashIsIn::isNormalUser($user));
    }

    /** @test */
    function it_determines_multi_channel_guest_users_correctly()
    {
        $user = app(SlackApiUserFactory::class)
            ->isRestricted()
            ->create();

        $this->assertTrue(SlashIsIn::isMultiChannelGuest($user));

        $this->assertFalse(SlashIsIn::isSingleChannelGuest($user));
        $this->assertFalse(SlashIsIn::isNormalUser($user));
    }

    /** @test */
    function it_determines_normal_users_correctly()
    {
        $user = app(SlackApiUserFactory::class)
            ->create();

        $this->assertTrue(SlashIsIn::isNormalUser($user));

        $this->assertFalse(SlashIsIn::isSingleChannelGuest($user));
        $this->assertFalse(SlashIsIn::isMultiChannelGuest($user));
    }
}
