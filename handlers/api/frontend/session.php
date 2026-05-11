<?php

use Wonder\Api\Endpoint;
use Wonder\Api\Handler;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\InviteCodeSession;

Handler::run('/api/rsvp/session/', 'GET', ['api_internal_user', 'api_public_access'], function (Endpoint $call) {
    return [
        'success' => true,
        'status' => 200,
        'response' => FrontendContext::state(InviteCodeSession::current()),
    ];
});
