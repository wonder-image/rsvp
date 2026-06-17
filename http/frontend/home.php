<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;

$state = require Rsvp::httpPath('frontend/context.php');

$requiresInviteCode = !empty($state['requires_invite_code']);
$hasSession = (int) (($state['session'] ?? [])['id'] ?? 0) > 0;

if ($requiresInviteCode && !$hasSession) {
    header('Location: '.__r('rsvp.login'), true, 302);
    exit();
}

$title = __t('pages.rsvp.home.title');
$description = __t('pages.rsvp.home.intro_text');
$seoImage = '';
$seoOverride = ExtensionRegistry::get()->seo('home', $state);

if (!empty($seoOverride['title'])) {
    $title = (string) $seoOverride['title'];
}
if (array_key_exists('description', $seoOverride)) {
    $description = (string) $seoOverride['description'];
}
if (!empty($seoOverride['image'])) {
    $seoImage = (string) $seoOverride['image'];
}

$GLOBALS['STATE'] = $state;
$GLOBALS['PAGE_KEY'] = 'rsvp.home';

\Wonder\View\View::make(Rsvp::viewPath('pages/frontend/home.php'), [
    'STATE' => $state,
    'PAGE_KEY' => 'rsvp.home',
    'SEO_TITLE' => $title,
    'SEO_DESCRIPTION' => $description,
    'SEO_URL' => __r('rsvp.home'),
    'SEO_BREADCRUMB' => [__r('rsvp.home') => 'RSVP'],
    'SEO_IMAGE' => $seoImage,
])->render();
