<?php

use Wonder\Plugin\Rsvp\Rsvp;

$state = is_array($STATE ?? null) ? $STATE : [];
$featuredEvent = is_array($state['featured_event'] ?? null) ? $state['featured_event'] : [];
$eventName = trim((string) ($featuredEvent['label'] ?? ($featuredEvent['name'] ?? '')));

$SEO = $GLOBALS['SEO'] ?? (object) [];
$SEO->title = (string) ($SEO_TITLE ?? '');
$SEO->description = (string) ($SEO_DESCRIPTION ?? '');
$SEO->url = (string) ($SEO_URL ?? '');
$SEO->breadcrumb = is_array($SEO_BREADCRUMB ?? null) ? $SEO_BREADCRUMB : [];

if (trim((string) ($SEO_IMAGE ?? '')) !== '') {
    $SEO->image = (string) $SEO_IMAGE;
}

$GLOBALS['SEO'] = $SEO;
$GLOBALS['PAGE_KEY'] = (string) ($PAGE_KEY ?? 'rsvp.home');

\Wonder\View\View::layout('frontend.main');

?>

<section class="mt-10">
    <div class="content a-c">
        <?php if ($eventName !== '') { ?>
            <h1 class="title-big tx-upper w-100"><?=e($eventName)?></h1>
        <?php } ?>
        <?php if (__t('pages.rsvp.home.title') !== $eventName) { ?>
            <p class="subtitle w-100 mt-4"><?=e(__t('pages.rsvp.home.title'))?></p>
        <?php } ?>
        <div class="text w-100 mt-4"><?=nl2br(e(__t('pages.rsvp.home.intro_text')))?></div>
    </div>
</section>

<section class="mt-10">
    <div class="content">
        <?php Rsvp::component('event-date'); ?>
    </div>
</section>

<section class="mt-10">
    <div class="content">
        <?php Rsvp::component('countdown'); ?>
    </div>
</section>

<?php Rsvp::component('form'); ?>

<?php \Wonder\View\View::end(); ?>
