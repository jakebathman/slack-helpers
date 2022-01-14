<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['app.api_key' => 'abcd1234']);
    }

    /** @test */
    public function middleware_allows_correct_key()
    {
        $response = $this->get(route('api.test', ['key' => 'abcd1234']));

        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_disallows_incorrect_key()
    {
        $response = $this->get(route('api.test', ['key' => 'wxyz0987']));

        $response->assertStatus(401);
    }

    /** @test */
    public function middleware_disallows_missing_key()
    {
        $response = $this->get(route('api.test'));

        $response->assertStatus(401);
    }
}
