<?php

    $TEXT = (object) array();
    $TEXT->titleS = "autorizzazione";
    $TEXT->titleP = "autorizzazioni";
    $TEXT->last = 'ultime'; // $TEXT->last 50 $TEXT->titleP
    $TEXT->all = 'tutte'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->article = 'le'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->full = 'usata'; // $TEXT->titleS $TEXT->full
    $TEXT->empty = 'non usata'; // $TEXT->titleS $TEXT->empty
    $TEXT->this = 'questa'; // Sei sicuro di voler eliminare $TEXT->this $TEXT->titleS

    $NAME = (object) array();
    $NAME->table = "rsvp_authority";
    $NAME->folder = "rsvp/authority";

    $BUTTON_ADD = true;

    $FILTER_TYPE = 'limit';

    $PAGE_TABLE = $TABLE->RSVP_AUTHORITY;

    $TABLE_ACTION = [ 
        'modify' => true,
        'delete' => true
    ];

    $TABLE_FIELD = [
        "empty" => [
            "function" => [
                "name" => "empty",
                "tables" => [ 'rsvp_password' ],
                "column" => "authority_id",
                "multiple" => true
            ]
        ],
        "name" => [
            "label" => "Autorizzazione",
            "href" => "modify"
        ],
        "code" => [
            "label" => "Codice",
            "phone" => false
        ]
    ];

    $FILTER_SEARCH = ['name', 'code'];