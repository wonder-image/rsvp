<?php

    $TEXT = (object) array();
    $TEXT->titleS = "risposta";
    $TEXT->titleP = "risposte";
    $TEXT->last = 'ultime'; // $TEXT->last 50 $TEXT->titleP
    $TEXT->all = 'tutte'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->article = 'le'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->full = ''; // $TEXT->titleS $TEXT->full
    $TEXT->empty = ''; // $TEXT->titleS $TEXT->empty
    $TEXT->this = 'questa'; // Sei sicuro di voler eliminare $TEXT->this $TEXT->titleS

    $NAME = (object) array();
    $NAME->table = "rsvp";
    $NAME->folder = "rsvp/response";

    $BUTTON_ADD = false;

    $BUTTON_CUSTOM = [
        [
            "value" => "<i class='bi bi-filetype-csv'></i> Scarica .csv",
            "action" => "href='$PATH->backend/rsvp/download.php?file=csv'"
        ],
        [
            "value" => "<i class='bi bi-filetype-xls'></i> Scarica .xls",
            "action" => "href='$PATH->backend/rsvp/download.php?file=xls'"
        ]
    ];

    $FILTER_TYPE = 'limit';

    $PAGE_TABLE = $TABLE->RSVP;

    $TABLE_ACTION = [ 
        'view' => true,
        'delete' => true
    ];

    $TABLE_FIELD = [
        "name" => [
            "label" => "Nome",
            "value" => ['name', 'surname'],
            "href" => "view"
        ],
        "email" => [
            "label" => "Email",
            "href" => "mailto",
            "tablet" => false
        ],
        "participants" => [
            "label" => "Partecipanti"
        ],
        "creation" => [
            "label" => "Conferma",
            "format" => "date",
            "orderable" => true,
            "dimension" => "medium"
        ]
    ];

    $FILTER_SEARCH = ['name', 'email'];

    $EVENTI = [
        "wedding" => "Wedding"
    ];