<?php

namespace Tests\Feature;

use App\Message;
use App\SlackUser;
use App\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Wgmv\SlackApi\Facades\SlackApi;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class GetStaffInTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_can_get_correct_statuses()
    {
        $token = factory(Token::class)->create();
        $teamId = $token->team_id;
        SlackUserClient::shouldReceive('info')
        ->withAnyArgs()
        ->andReturnUsing(function ($userId) {
            $user = SlackUser::where('slack_id', $userId)->first();

            return (object)[
                'user' => (object)[
                'id' => $user->slack_id,
                'team_id' => $user->team_id,
                'profile' => (object)[
                    'display_name' => $user->display_name,
                ],
                'color' => $user->color,
                'real_name' => $user->real_name,
                'tz' => $user->tz,
                'updated' => $user->updated,
                ],
            ];
        });

        SlackApi::shouldReceive('http', 'post')
                ->withAnyArgs();

        $users = factory(SlackUser::class, 5)->create([
            'team_id' => $teamId,
        ]);

        $messages = $users->map(function ($user) {
            $m[] = factory(Message::class)->make([
                'user' => $user->slack_id,
                'text' => '@in',
                'time' => time() - 100,
                'team' => $user->team_id,
            ]);

            $m[] = factory(Message::class)->make([
                'user' => $user->slack_id,
                'text' => '@lunch',
                'time' => time() - 50,
                'team' => $user->team_id,
            ]);

            $m[] = factory(Message::class)->make([
                'user' => $user->slack_id,
                'text' => '@back',
                'time' => time() - 25,
                'team' => $user->team_id,
            ]);

            $m[] = factory(Message::class)->make([
                'user' => $user->slack_id,
                'text' => '@out',
                'time' => time() - 10,
                'team' => $user->team_id,
            ]);

            return $m;
        })
        ->flatten();

        // dump($users->toArray());

        $apiResponse = (object)[
            'ok' => true,
            'messages' => $messages,
            'has_more' => true,
        ];

        // dd($apiResponse);

        // SlackUserClient::shouldReceive('info')
        //     ->with('user')
        //     ->andReturn((new FakeSlackUserClient())->info('user'));

        SlackChannel::shouldReceive('history')
            ->withAnyArgs()
            ->andReturn($apiResponse);

        $data = $this->get(route('in', ['teamId' => $teamId]));
        // dd(route('in', ['teamId' => $teamId]));
        // $data->dump();

        $this->markTestIncomplete();

        $this->assertTrue(true);
    }
}
