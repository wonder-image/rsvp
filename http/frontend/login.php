<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;

$state = require Rsvp::httpPath('frontend/context.php');
$title = __t('pages.rsvp.login.title');
$description = __t('pages.rsvp.login.text');
$seoImage = '';
$seoOverride = ExtensionRegistry::get()->seo('login', $state);

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
$GLOBALS['PAGE_KEY'] = 'rsvp.login';

\Wonder\View\View::make(Rsvp::viewPath('pages/frontend/login.php'), [
    'STATE' => $state,
    'PAGE_KEY' => 'rsvp.login',
    'SEO_TITLE' => $title,
    'SEO_DESCRIPTION' => $description,
    'SEO_URL' => __r('rsvp.login'),
    'SEO_BREADCRUMB' => [__r('rsvp.login') => 'RSVP'],
    'SEO_IMAGE' => $seoImage,
])->render();
