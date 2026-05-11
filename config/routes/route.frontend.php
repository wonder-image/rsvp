<?php

use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Rsvp;

Route::area('frontend')
    ->group(function () {
        Route::name('rsvp.')
            ->prefix('/rsvp')
            ->group(function () {
                Route::get('/', Rsvp::handlerPath('frontend/home.php'))
                    ->name('home');

                Route::get('/login/', Rsvp::handlerPath('frontend/login.php'))
                    ->name('login');
            });
    });
