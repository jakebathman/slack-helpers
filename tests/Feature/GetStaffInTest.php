<?php

namespace Tests\Feature;

use App\Message;
use App\SlackClient;
use App\SlackUser;
use App\Token;
use Illuminate\Support\Arr;
use Tests\Fakes\FakeSlackClient;
use Tests\TestCase;
use Wgmv\SlackApi\Facades\SlackChannel;

class GetStaffInTest extends TestCase
{
    /** @test */
    function it_can_get_correct_statuses_test()
    {
        $token = factory(Token::class)->create();
        $teamId = $token->team_id;
        // app()->instance(SlackClient::class, (new FakeSlackClient($teamId)));
        dump('teamId', $teamId);

        $users = factory(SlackUser::class, 5)->create();
        $messages = factory(Message::class, 10)->make([
            'user' => Arr::random($users->pluck('slack_id')->toArray()),
        ]);

        $apiResponse = (object)[
            'ok' => true,
            'messages' => $messages,
            'has_more' => true,
        ];

        // SlackUserClient::shouldReceive('info')
        //     ->with('userId')
        //     ->andReturn(factory(SlackUser::class)->make());

        SlackChannel::shouldReceive('history')
            ->once()
            ->withAnyArgs()
            ->andReturn($apiResponse);

        $data = $this->get(route('slash.in', ['teamId' => $teamId]));

        $this->assertTrue(true);
    }

    function getChannelHistory()
    {
        return collect(
            [

            ]
        );
    }
}
