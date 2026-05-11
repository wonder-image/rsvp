<?php

use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\FrontendPage;

$state = FrontendContext::state();
$title = (string) ($state['login_title'] ?? 'Accesso RSVP');
$description = trim(strip_tags((string) ($state['login_text'] ?? '')));

FrontendPage::render(
    'rsvp.login',
    $title,
    $description,
    function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/',
    Rsvp::viewPath('frontend/login.php'),
    ['STATE' => $state]
);
