<?php

    $TEXT = (object) array();
    $TEXT->titleS = "invitato";
    $TEXT->titleP = "invitati";
    $TEXT->last = 'ultimi'; // $TEXT->last 50 $TEXT->titleP
    $TEXT->all = 'tutti'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->article = 'gli'; // Lista $TEXT->all $TEXT->article $titlePageP
    $TEXT->full = 'usato'; // $TEXT->titleS $TEXT->full
    $TEXT->empty = 'non usato'; // $TEXT->titleS $TEXT->empty
    $TEXT->this = 'questo'; // Sei sicuro di voler eliminare $TEXT->this $TEXT->titleS

    $NAME = (object) array();
    $NAME->table = "rsvp";
    $NAME->folder = "rsvp";

    $BUTTON_ADD = false;

    $BUTTON_CUSTOM = [
        [
            "value" => "Scarica .csv",
            "action" => "href='$PATH->backend/rsvp/download.php?file=csv'"
        ]
    ];

    $FILTER_TYPE = 'limit';

    $PAGE_TABLE = $TABLE->RSVP;

    $TABLE_ACTION = [ 
        'modify' => true
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
        "partecipants" => [
            "label" => ""
        ]
    ];

    $FILTER_SEARCH = ['name', 'email'];

?>