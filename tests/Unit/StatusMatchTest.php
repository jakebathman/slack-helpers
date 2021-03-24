<?php

namespace Tests\Unit;

use App\Http\Controllers\GetStaffIn;
use App\StatusMatcher;
use Tests\TestCase;

class StatusMatchTest extends TestCase
{
    /** @test */
    function it_matches_in_status()
    {
        $true = [
            '@in',
            '!in',
            '+in',
            '    @in    ',
            'in',
            '@ingrid',
            '@​ingrid', // this has a zero-width space (U+200B) after @
            "I'm finally @in!",
            '@innie',
            '@iinne',
        ];

        $false = [
            '',
            'in!',
            'interior',
            '@inside',
            'fake@inbox.com',
        ];

        $this->runRegexTests('hasIn', $true, $false);
    }

    /** @test */
    function it_matches_break_status()
    {
        $true = [
            '@break',
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
        ];

        $false = [
            '',
            'breakdancing',
            'fake@breakdance.net',
            'Did you break the site?',
        ];

        $this->runRegexTests('hasBreak', $true, $false);
    }

    /** @test */
    function it_matches_lunch_status()
    {
        $true = [
            '@lunch',
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
        ];

        $false = [
            '',
            'lunchroom',
            'fake@lunchface.com',
            'Amanda makes the best lunch for me',
        ];

        $this->runRegexTests('hasLunch', $true, $false);
    }

    /** @test */
    function it_matches_back_status()
    {
        $true = [
            '@back',
            '!back',
            '+back',
            '    @back    ',
            'back',
            "I'm @back!",
        ];

        $false = [
            '',
            'backside',
            'fake@backstop.com',
            'I tweaked my back yesterday',
        ];

        $this->runRegexTests('hasBack', $true, $false);
    }

    /** @test */
    function it_matches_out_status()
    {
        $true = [
            '@out',
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
