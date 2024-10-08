<?php

    $NAV_BACKEND = [
        [
            'title' => 'RSVP',
            'folder' => 'rsvp/response',
            'icon' => 'bi-people',
            'file' => 'list.php',
            'authority' => [],
            'subnavs' => []
        ],
        [
            'title' => 'Dettagli',
            'folder' => 'rsvp/config',
            'icon' => 'bi-file',
            'file' => '',
            'authority' => [ 'admin' ],
            'subnavs' => []
        ],
        [
            'title' => 'Password',
            'folder' => 'password',
            'icon' => 'bi-key',
            'authority' => [ 'admin' ],
            'subnavs' => [
                [
                    'title' => 'Password',
                    'folder' => 'rsvp/password',
                    'file' => 'list.php',
                    'authority' => [ 'admin' ]
                ],
                [
                    'title' => 'Autorizzazioni',
                    'folder' => 'rsvp/authority',
                    'file' => 'list.php',
                    'authority' => [ 'admin' ]
                ]
            ]
        ]
    ];