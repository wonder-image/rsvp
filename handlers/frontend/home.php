<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
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

// Override SEO dal consumer (RsvpExtension::seo). Title/description sono
// passati per parametro a FrontendPage::render; image va settata sul
// global $SEO PRIMA del render perché head.php legge da $GLOBALS['SEO']
// e FrontendPage::render non resetta la chiave `image`.
$seoOverride = ExtensionRegistry::get()->seo('home', $state);

if (!empty($seoOverride['title'])) {
    $title = (string) $seoOverride['title'];
}
if (array_key_exists('description', $seoOverride)) {
    $description = (string) $seoOverride['description'];
}
if (!empty($seoOverride['image'])) {
    $GLOBALS['SEO'] = $GLOBALS['SEO'] ?? (object) [];
    $GLOBALS['SEO']->image = (string) $seoOverride['image'];
}

FrontendPage::render(
    'rsvp.home',
    $title,
    $description,
    function_exists('__r') ? __r('rsvp.home') : '/rsvp/',
    Rsvp::viewPath('frontend/home.php'),
    ['STATE' => $state]
);
