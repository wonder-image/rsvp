<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\FrontendPage;

$state = FrontendContext::state();
$title = (string) ($state['login_title'] ?? 'Accesso RSVP');
$description = trim(strip_tags((string) ($state['login_text'] ?? '')));

// Override SEO dal consumer (RsvpExtension::seo).
$seoOverride = ExtensionRegistry::get()->seo('login', $state);

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
    'rsvp.login',
    $title,
    $description,
    function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/',
    Rsvp::viewPath('frontend/login.php'),
    ['STATE' => $state]
);
