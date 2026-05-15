<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use App\Http\Controllers\GetStaffIn;
use App\StatusMatcher;
use Tests\TestCase;

class StatusMatchTest extends TestCase
{
    #[Test]
    function it_matches_in_status()
    {
        $true = [
            '@in',
            '$in',
            '!in',
            '+in',
            '    @in    ',
            'in',
            '@ingrid',
            '@​ingrid', // this has a zero-width space (U+200B) after @
            "I'm finally @in!",
            '@innie',
            '@iinne',
            '@inward',
            '@ihavearrived',
            '<!subteam^SJJ4NPRNU>', // old @in user group
            '<@U0AQ3SPNQNT>', // @in bot user
            '<@U0AQ3SPNQNT> :spiderman-dance:', // @in bot user
        ];

        $false = [
            '',
            '$3.95',
            'in!',
            'interior',
            '@inside',
            'fake@inbox.com',
        ];

        $this->runRegexTests('hasIn', $true, $false);
    }

    #[Test]
    function it_matches_break_status()
    {
        $true = [
            '@break',
            '$break',
            '!break',
            '+break',
            '    @break    ',
            '@brb',
            '!brb',
            '+brb',
            '    @brb    ',
            '@relo',
            ':coffee:',
            ':latte:',
            ':tea:',
            ':tea: :timer_clock:',
            ':tea_cat: :timer_clock:',
            '@walk',
            ':coffee_cat:',
            ':diet-coke:',
            ':dietcoke:',
            '@lilbreakybreak',
            ':coffin:',
            '<@U0AQR1P837Y>', // @break bot user
        ];

        $false = [
            '',
            'breakdancing',
            'fake@breakdance.net',
            'Did you break the site?',
        ];

        $this->runRegexTests('hasBreak', $true, $false);
    }

    #[Test]
    function it_matches_lunch_status()
    {
        $true = [
            '@lunch',
            '$lunch',
            '!lunch',
            '+lunch',
            'lunch',
            '    @lunch    ',
            'grabbing @lunch',
            'lunch time',
            '@brunch',
            '@lunching',
            '@snack',
            '@snacking',
            '@lunchito',
            '@dinner',
            '@breakfast',
            '@luncheon',
            '@banquet',
            '<@U0ARMBJRB0Q>', // @lunch bot user
        ];

        $false = [
            '',
            'lunchroom',
            'fake@lunchface.com',
            'Amanda makes the best lunch for me',
        ];

        $this->runRegexTests('hasLunch', $true, $false);
    }

    #[Test]
    function it_matches_back_status()
    {
        $true = [
            '@back',
            '$back',
            '!back',
            '+back',
            '    @back    ',
            'back',
            "I'm @back!",
            '<@U0AQPLWPC1K>', // @back bot user
        ];

        $false = [
            '',
            'backside',
            'fake@backstop.com',
            'I tweaked my back yesterday',
        ];

        $this->runRegexTests('hasBack', $true, $false);
    }

    #[Test]
    function it_matches_out_status()
    {
        $true = [
            '@out',
            '$out',
            '!out',
            '+out',
            '    @out    ',
            'out',
            '@oot',
            '@ofnbl',
            '@notin',
            '@vote',
            '@voting',
            '@ofn',
            '@therapy',
            '@errand',
            '@errands',
            '@nap',
            '@outtie',
            '@outties',
            '@outage',
            '@outward',
            '@farewellminions',
            '@workout',
            '<!subteam^S013Y6JHHAM>', // old @out user group
            '<@U0AQLMQ9L03>', // @out bot user
        ];

        $false = [
            '',
            'outside',
            'fake@outbacksteakhouse.com',
        ];

        $this->runRegexTests('hasOut', $true, $false);
    }

    function runRegexTests($method, $true, $false)
    {
        // Test for affirmative matches
        foreach ($true as $test) {
            $this->assertEquals(1, StatusMatcher::$method($test), "Failed positive match: '{$test}'");
        }

        // Test for negative matches
        foreach ($false as $test) {
            $this->assertEquals(0, StatusMatcher::$method($test), "Failed negative match: '{$test}'");
        }
    }
}
