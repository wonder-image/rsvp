<?php

    if (sqlSelect('rsvp_details', ['id' => 1], 1)->Nrow == 0) {
                
        sqlInsert('rsvp_details', []);
        
    }

    sqlModify('css_default', [ 'header_height' => "120" ], 'id', 1);