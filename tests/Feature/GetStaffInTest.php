<?php

namespace Tests\Feature;

use App\Message;
use App\SlackUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class GetStaffInTest extends TestCase
{
    protected function getChannelHistory()
    {
        return collect(
            [

            ]
        );
    }

    /** @test */
    public function it_can_get_correct_statuses_test()
    {
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

        $data = $this->get('in')->dump();

        $this->assertTrue(true);
    }
}
