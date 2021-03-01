<?php

namespace Tests\Feature;

use App\Exceptions\SlackApiVerificationException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlackMiddlewareTest extends TestCase
{
    protected $timestamp;

    function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        $this->timestamp = Carbon::now()->timestamp;
    }

    /** @test */
    function middleware_correctly_validates_signature()
    {
        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature(),
        ])
        ->get('/api/slack/test?foo=bar');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /** @test */
    function middleware_correctly_validates_signature_with_request_body()
    {
        $body = ['text' => 'foo bar'];

        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature($body),
        ])
        ->json('POST', '/api/slack/test?foo=bar', $body);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /** @test */
    function middleware_rejects_invalid_signature()
    {
        $this->expectException(SlackApiVerificationException::class);

        // Create a signature with a different body than the request will have
        $response = $this->withHeaders([
            'X-Slack-Request-Timestamp' => $this->timestamp,
            'X-Slack-Signature' => $this->getHeaderSignature('?foo=bar'),
        ])
        ->get('/api/slack/test');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    function getHeaderSignature($body = '')
    {
        $body = is_array($body) ? json_encode($body) : $body;
        $basestring = "v0:{$this->timestamp}:{$body}";

        return 'v0=' . hash_hmac('sha256', $basestring, config('services.slack.signing_secret'));
    }
}
