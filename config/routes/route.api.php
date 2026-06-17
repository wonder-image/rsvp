<?php

use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Rsvp;

Route::area('api')
    ->response('json')
    ->group(function () {
        Route::name('api.rsvp.')
            ->prefix('/rsvp')
            ->group(function () {
                // La submission del form RSVP usa la rotta resource standard
                // `api.resource.rsvp-responses.store` (vedi ResponseResource):
                // normalizzazione, validazione RSVP e notifiche vivono lì.

                Route::post('/login/', Rsvp::handlerPath('api/frontend/login.php'))
                    ->name('login');

                Route::post('/logout/', Rsvp::handlerPath('api/frontend/logout.php'))
                    ->name('logout');

                Route::get('/session/', Rsvp::handlerPath('api/frontend/session.php'))
                    ->name('session');
            });
    });
