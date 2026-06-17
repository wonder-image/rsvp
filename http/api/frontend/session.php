<?php

use Wonder\Api\Endpoint;
use Wonder\Api\Handler;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Services\InviteCodeSession;

Handler::run('/api/rsvp/session/', 'GET', ['api_internal_user', 'api_public_access'], function (Endpoint $call) {
    $session = InviteCodeSession::current();

    return [
        'success' => true,
        'status' => 200,
        'response' => (static function () use ($session) {
            return require Rsvp::httpPath('frontend/context.php');
        })(),
    ];
});
