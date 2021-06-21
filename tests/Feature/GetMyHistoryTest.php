<?php

namespace Tests\Feature;

use App\Factories\SlackApiMessageFactory;
use App\Http\Controllers\GetStaffIn;
use App\SlackUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetMyHistoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_can_list_user_status_message_history()
    {
        $this->markTestIncomplete();
        $user = factory(SlackUser::class)->create();
        $userId = $user->slack_id;

        // Make some messages for the user, over multiple days
        // with status message interspersed
        $messages = collect()
            ->merge((new SlackApiMessageFactory)->withText('@in')->create())
            ->merge((new SlackApiMessageFactory)->withText('@out')->create())
            ->toArray();

        $status = (new GetStaffIn)->getUserStatus($user, $messages);

        $this->assertEquals($status['slack_id'], $userId);
        $this->assertEquals($status['last_message'], '@out');
        $this->assertEquals($status['status'], 'out');
    }
}
