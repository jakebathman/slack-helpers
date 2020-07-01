<?php

namespace Tests\Feature;

use App\Factories\SlackApiMessageFactory;
use App\Http\Controllers\GetStaffIn;
use App\SlackUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetStaffInTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_can_get_correct_out_user_status()
    {
        $user = factory(SlackUser::class)->create();
        $userId = $user->slack_id;

        // Make some messages for the user,
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

    /** @test */
    function it_can_get_correct_in_user_status()
    {
        $user = factory(SlackUser::class)->create();
        $userId = $user->slack_id;

        // Make some messages for the user,
        // with status message interspersed
        $messages = collect()
            ->merge((new SlackApiMessageFactory)->withText('@in')->create())
            ->merge((new SlackApiMessageFactory)->times(2)->create())
            ->toArray();

        $status = (new GetStaffIn)->getUserStatus($user, $messages);

        $this->assertEquals($status['slack_id'], $userId);
        $this->assertEquals($status['last_message'], '@in');
        $this->assertEquals($status['status'], 'in');
    }

    /** @test */
    function it_prepares_user_messages()
    {
        $messages = collect()
            ->merge((new SlackApiMessageFactory)->times(2)->create())
            ->merge((new SlackApiMessageFactory)->withText('@in')->create())
            ->merge((new SlackApiMessageFactory)->times(3)->create())
            ->merge((new SlackApiMessageFactory)->withText('@out')->create())
            ->merge((new SlackApiMessageFactory)->times(5)->create())
            ->toArray();

        $this->assertCount(12, $messages);

        $prepared = (new GetStaffIn)->prepareMessages($messages);

        $this->assertCount(2, $prepared);
        $this->assertEquals('@out', $prepared->first()['text']);
        $this->assertEquals('@in', $prepared->last()['text']);
    }

    /** @test */
    function it_parses_at_mentions_to_text()
    {
        // Only user group and special mentions should be parsed
        $strings = [
            '<!subteam^S12345678|@foo>' => '@foo',
            '<!here>' => '@here',
            '<!channel>' => '@channel',
            '<!everyone>' => '@everyone',
            '<!subteam^S12345678|@foo> and <!subteam^S98765432|@bar>' => '@foo and @bar',

            // These should all return unchanged
            '<@U1234ABCD>' => '<@U1234ABCD>',
            '<#C0838UC2D|general>' => '<#C0838UC2D|general>',
            '<http://example.com|example link>' => '<http://example.com|example link>',
        ];

        foreach ($strings as $input => $output) {
            $this->assertEquals($output, GetStaffIn::parseSpecialMentionsToText($input));
        }
    }
}
