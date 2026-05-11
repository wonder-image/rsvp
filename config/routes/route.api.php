<?php

use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Rsvp;

Route::area('api')
    ->prefix('/api')
    ->response('json')
    ->group(function () {
        Route::name('api.rsvp.')
            ->prefix('/rsvp')
            ->group(function () {
                Route::post('/', Rsvp::handlerPath('api/frontend/submit.php'))
                    ->name('submit');

                Route::post('/login/', Rsvp::handlerPath('api/frontend/login.php'))
                    ->name('login');

                Route::post('/logout/', Rsvp::handlerPath('api/frontend/logout.php'))
                    ->name('logout');

                Route::get('/session/', Rsvp::handlerPath('api/frontend/session.php'))
                    ->name('session');
            });
    });
