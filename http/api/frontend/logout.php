<?php

use Wonder\Api\Endpoint;
use Wonder\Api\Handler;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Services\InviteCodeSession;

Handler::run('/api/rsvp/logout/', 'POST', ['api_internal_user', 'api_public_access'], function (Endpoint $call) {
    InviteCodeSession::logout();
    $session = [];

    return [
        'success' => true,
        'status' => 200,
        'response' => Rsvp::context($session),
    ];
});
