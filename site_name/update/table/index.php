<?php

    $PRIVATE = false;
    $PERMIT = [];

    $FRONTEND = true;

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    foreach ($TABLE as $table => $value) {
        
        $table_name = strtolower($table);
        $table_column = $value;

        sqlTable($table_name, $table_column);
        
    }
    
?>