<?php

$SEO = $GLOBALS['SEO'] ?? (object) [];
$SEO->title = (string) ($SEO_TITLE ?? '');
$SEO->description = (string) ($SEO_DESCRIPTION ?? '');
$SEO->url = (string) ($SEO_URL ?? '');
$SEO->breadcrumb = is_array($SEO_BREADCRUMB ?? null) ? $SEO_BREADCRUMB : [];

if (trim((string) ($SEO_IMAGE ?? '')) !== '') {
    $SEO->image = (string) $SEO_IMAGE;
}

$GLOBALS['SEO'] = $SEO;
$GLOBALS['PAGE_KEY'] = (string) ($PAGE_KEY ?? 'rsvp.page');

\Wonder\View\View::layout('frontend.main');

include $VIEW_PATH;

\Wonder\View\View::end();
