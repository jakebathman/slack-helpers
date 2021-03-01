<?php

namespace App\Http\Middleware;

use App\Exceptions\SlackApiVerificationException;
use Closure;
use Illuminate\Support\Carbon;

class SlackApiVerification
{
    /**
     * Verify that a request has come from Slack for our app.
     * Ensure that the Signing Secret from the Slack app management
     * page is in .env as SLACK_APP_SIGNING_SECRET
     *
     * Verification combines a version string, the X-Slack-Request-Timestamp header,
     * and the request body, and hashes them using HMAC SHA256 with our Signing Secret.
     * This is compared to the hash in the header X-Slack-Signature to ensure they match
     *
     * More info: https://api.slack.com/authentication/verifying-requests-from-slack
     */
    public function handle($request, Closure $next)
    {
        // Version number (always v0, according to slack)
        $version = 'v0';

        // Get the timestamp header value and ensure it's within 5 minutes from now
        $timestamp = $request->header('X-Slack-Request-Timestamp');

        if (Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($timestamp)) > 5) {
            throw new SlackApiVerificationException('Invalid X-Slack-Request-Timestamp, difference is greater than 5 minutes. See Slack API documentation for Signing Signature verification for more info: https://api.slack.com/authentication/verifying-requests-from-slack#a_recipe_for_security');
        }

        // Get the request content
        $body = $request->getContent();

        // Concatenate to make the basestring
        $basestring = "{$version}:{$timestamp}:{$body}";

        // Load the Signing Secret from .env
        $secret = config('services.slack.signing_secret');

        // Hash basestring using our Signing Secret as the key
        $hash = hash_hmac('sha256', $basestring, $secret);

        // Prefix the version to the hash to make a signature
        $signature = "{$version}={$hash}";

        // Check that the signature in the request header matches the one we just made
        if ($signature !== $request->header('X-Slack-Signature')) {
            throw new SlackApiVerificationException('Invalid signature when attempting to validate incoming Slack request in SlackApiVerification middleware.');
        }

        // Signature verified!
        return $next($request);
    }
}
