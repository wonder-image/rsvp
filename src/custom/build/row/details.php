<?php

    if (sqlSelect('rsvp_details', ['id' => 1], 1)->Nrow == 0) {
                
        sqlInsert('rsvp_details', []);
        
    }