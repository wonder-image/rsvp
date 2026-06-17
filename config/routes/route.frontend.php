<?php

use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Rsvp;

Route::area('frontend')
    ->group(function () {
        Route::name('rsvp.')
            ->prefix('/rsvp')
            ->group(function () {
                Route::get('/', Rsvp::httpPath('frontend/home.php'))
                    ->name('home');

                Route::get('/login/', Rsvp::httpPath('frontend/login.php'))
                    ->name('login');
            });
    });
