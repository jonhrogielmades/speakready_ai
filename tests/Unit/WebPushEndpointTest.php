<?php

namespace Tests\Unit;

use App\Support\WebPushEndpoint;
use Tests\TestCase;

class WebPushEndpointTest extends TestCase
{
    public function test_it_allows_only_https_endpoints_from_configured_push_services(): void
    {
        config(['services.webpush.allowed_endpoint_hosts' => [
            'fcm.googleapis.com',
            'push.services.mozilla.com',
        ]]);

        $this->assertTrue(WebPushEndpoint::isAllowed('https://fcm.googleapis.com/fcm/send/token'));
        $this->assertTrue(WebPushEndpoint::isAllowed('https://updates.push.services.mozilla.com/wpush/v2/token'));
        $this->assertFalse(WebPushEndpoint::isAllowed('http://fcm.googleapis.com/fcm/send/token'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://fcm.googleapis.com:8443/fcm/send/token'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://fcm.googleapis.com/fcm/send/token#fragment'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://attacker.example/fcm.googleapis.com'));
    }

    public function test_it_rejects_ip_literals_credentials_and_internal_hosts(): void
    {
        config(['services.webpush.allowed_endpoint_hosts' => ['push.example.com']]);

        $this->assertFalse(WebPushEndpoint::isAllowed('https://127.0.0.1/subscription'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://[::1]/subscription'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://user:secret@push.example.com/subscription'));
        $this->assertFalse(WebPushEndpoint::isAllowed('https://localhost/subscription'));
    }
}
