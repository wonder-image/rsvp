<?php

use Wonder\Api\Endpoint;
use Wonder\Api\Handler;
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\InviteCodeSession;
use Wonder\Plugin\Rsvp\Support\SubmissionNormalizer;
use Wonder\Plugin\Rsvp\Support\SubmissionNotifier;

Handler::run('/api/rsvp/', 'POST', ['api_internal_user', 'api_public_access'], function (Endpoint $call) {
    if (isset($call->parameters['g-recaptcha-token'])) {
        verifyRecaptcha($call->parameters);

        if (!empty($GLOBALS['ALERT'])) {
            throw new RuntimeException((string) __t('notifications.'.$GLOBALS['ALERT'].'.text'), (int) $GLOBALS['ALERT']);
        }
    }

    $normalized = SubmissionNormalizer::fromPayload($call->parameters);
    $state = FrontendContext::state();

    if (trim((string) ($normalized['contact_email'] ?? '')) === '') {
        throw new RuntimeException('Email mancante.');
    }

    if ((int) ($normalized['participants_count'] ?? 0) <= 0) {
        throw new RuntimeException('Partecipanti mancanti.');
    }

    $session = InviteCodeSession::current();

    if (($state['requires_invite_code'] ?? false) && ($session['id'] ?? 0) <= 0) {
        throw new RuntimeException('Questo RSVP richiede un codice invito valido.');
    }

    if (($session['id'] ?? 0) > 0 && !($session['can_submit'] ?? true)) {
        throw new RuntimeException('Il codice invito ha già esaurito gli invii disponibili.');
    }

    $consents = SubmissionNormalizer::consents($normalized);

    if (empty($consents['privacy'])) {
        throw new RuntimeException('È necessario accettare la privacy.');
    }

    if (($state['require_image_release'] ?? false) && empty($consents['photo'])) {
        throw new RuntimeException('È necessario accettare la liberatoria immagini.');
    }

    $insert = Response::query()->Insert(Response::$table, $normalized);

    if (empty($insert->success)) {
        $message = (string) ($insert->response->alert->message ?? 'Salvataggio RSVP non riuscito.');
        throw new RuntimeException($message);
    }

    $responseId = (int) ($insert->insert_id ?? 0);

    SubmissionNotifier::notify($normalized, $responseId);

    return $call->response(rsvp_trans(
        'notifications.649.text',
        'Risposta RSVP inviata con successo.'
    ));
});
