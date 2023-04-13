<?php

    $FRONTEND = true;
    $PRIVATE = false;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    if ($_POST['post']) { 

        $FORM = json_decode($_POST['form']);

        $VALUES = [];

        foreach ($FORM as $key => $value) {

            if (is_array($value)) {
                $VALUES[$key] = json_encode($value);
            } else {
                $VALUES[$key] = sanitize($value);
            }

        }
        
        sqlInsert('rsvp', $VALUES);
    
    }

?>