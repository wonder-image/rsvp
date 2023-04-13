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
        ]
    ];

    $FILTER_SEARCH = ['name', 'email'];

    $EVENTI = [
        "pool-party" => "Pool Party",
        "wedding" => "Beach Wedding Day",
        "brunch" => "Brunch"
    ];

?>