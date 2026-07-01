<?php

use Wonder\Plugin\Rsvp\Rsvp;

Rsvp::auth();

$SEO->title = __t('pages.rsvp.home.seo.title');
$SEO->description = __t('pages.rsvp.home.seo.description');
$SEO->url = __r('rsvp.home');
$SEO->breadcrumb = [];

$GLOBALS['PAGE_KEY'] = 'rsvp.home';

$event = Rsvp::context()['featured_event'] ?? [];
$eventName = trim((string) ($event['label'] ?? ($event['name'] ?? '')));

Rsvp::layout('main');

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
