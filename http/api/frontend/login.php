<?php

use Wonder\Api\Endpoint;
use Wonder\Api\Handler;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Services\InviteCodeSession;

Handler::run('/api/rsvp/login/', 'POST', ['api_internal_user', 'api_public_access'], function (Endpoint $call) {
    $allowedGroups = $call->parameters['allowed_groups'] ?? [];

    if (!is_array($allowedGroups)) {
        $allowedGroups = [$allowedGroups];
    }

    $allowedGroups = array_values(array_filter(array_map(
        static fn ($value) => is_scalar($value) ? trim((string) $value) : '',
        $allowedGroups
    )));

    $code = (string) ($call->parameters['code'] ?? ($call->parameters['password'] ?? ''));
    $session = InviteCodeSession::login($code, $allowedGroups);

    return [
        'success' => true,
        'status' => 200,
        'response' => (static function () use ($session) {
            return require Rsvp::httpPath('frontend/context.php');
        })(),
    ];
});
