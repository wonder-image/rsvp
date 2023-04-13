<?php

    $NAV_BACKEND = [
        [
            'title' => 'RSVP',
            'folder' => 'rsvp',
            'icon' => 'bi-people',
            'file' => 'list.php',
            'authority' => [],
            'subnavs' => []
        ],
        [
            'title' => 'Dettagli',
            'folder' => 'rsvp_details',
            'icon' => 'bi-file',
            'file' => '',
            'authority' => ['admin'],
            'subnavs' => []
        ],
        [
            'title' => 'Password',
            'folder' => 'password',
            'icon' => 'bi-key',
            'authority' => ['admin'],
            'subnavs' => [
                [
                    'title' => 'Password',
                    'folder' => 'rsvp_password',
                    'file' => 'list.php',
                    'authority' => ['admin']
                ],
                [
                    'title' => 'Autorizzazioni',
                    'folder' => 'rsvp_authority',
                    'file' => 'list.php',
                    'authority' => ['admin']
                ]
            ]
        ]
    ];

?>