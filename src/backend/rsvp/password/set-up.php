<?php

    $TEXT = (object) array();
    $TEXT->titleS = "password";
    $TEXT->titleP = "password";
    $TEXT->last = 'ultime'; // $TEXT->last 50 $TEXT->titleP
    $TEXT->all = 'tutte'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->article = 'le'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->full = 'usata'; // $TEXT->titleS $TEXT->full
    $TEXT->empty = 'non usata'; // $TEXT->titleS $TEXT->empty
    $TEXT->this = 'questa'; // Sei sicuro di voler eliminare $TEXT->this $TEXT->titleS

    $NAME = (object) array();
    $NAME->table = "rsvp_password";
    $NAME->folder = "rsvp/password";

    $TYPE = [
        "single_use" => "Monouso",
        "multiple_use" => "Multiuso"
    ];

    $BUTTON_ADD = true;

    $FILTER_CUSTOM = [
        "type" => [
            'column' => 'type',
            'array' => array_merge(['' => 'Tutti'], $TYPE),
            'name' => 'Tipologia',
            'type' => 'radio'
        ],
        "active" => [
            'database' => false,
            'column' => 'active',
            'name' => 'Stato',
            'search' => false,
            'type' => 'radio'
        ]
    ];

    $FILTER_TYPE = 'limit';

    $PAGE_TABLE = $TABLE->RSVP_PASSWORD;

    $TABLE_ACTION = [ 
        'modify' => true,
        'delete' => true
    ];

    $TABLE_FIELD = [
        "password" => [
            "label" => "Password",
            "href" => "modify"
        ],
        "type" => [
            "label" => "Tipologia",
            "phone" => false
        ],
        "active" => [
            "function" => [
                "name" => "active",
                "return" => "automaticResize"
            ]
        ]
    ];

    $FILTER_SEARCH = ['password'];