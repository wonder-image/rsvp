<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\FrontendPage;

$state = FrontendContext::state();

// Se l'RSVP richiede un codice invito e l'utente non ha una sessione
// valida, rimanda subito al login: la home non deve neanche essere
// renderizzata (niente preview dei dati evento).
$requiresInviteCode = !empty($state['requires_invite_code']);
$hasSession = (int) (($state['session'] ?? [])['id'] ?? 0) > 0;

if ($requiresInviteCode && !$hasSession) {
    $loginUrl = function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/';
    header('Location: '.$loginUrl, true, 302);
    exit();
}

$title = (string) ($state['home_title'] ?? 'RSVP');
$description = trim(strip_tags((string) ($state['home_text'] ?? '')));

FrontendPage::render(
    'rsvp.home',
    $title,
    $description,
    function_exists('__r') ? __r('rsvp.home') : '/rsvp/',
    Rsvp::viewPath('frontend/home.php'),
    ['STATE' => $state]
);
