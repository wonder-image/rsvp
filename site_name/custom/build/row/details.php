<?php

    if (sqlSelect('details', ['id' => 1], 1)->Nrow == 0) {
                
        sqlInsert('details', []);
        
    }

    sqlModify('css_default', ['header_height' => "120"], 'id', 1);

?>