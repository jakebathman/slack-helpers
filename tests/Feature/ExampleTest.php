<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
