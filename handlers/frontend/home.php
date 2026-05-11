<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\FrontendPage;

$state = FrontendContext::state();
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
