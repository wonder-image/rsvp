<?php

    $PRIVATE = false;
    $PERMIT = [];

    $FRONTEND = true;

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    $files = empty(scandir("$ROOT_APP/build/row/")) ? [] : scandir("$ROOT_APP/build/row/");

    foreach ($files as $file) {
        if ($file != '' && $file != '.' && $file != '..') {
            include "$ROOT_APP/build/row/$file";
        }
    }

    $files = empty(scandir("$ROOT/custom/build/row/")) ? [] : scandir("$ROOT/custom/build/row/");

    foreach ($files as $file) {
        if ($file != '' && $file != '.' && $file != '..') {
            include "$ROOT/custom/build/row/$file";
        }
    }
    
?>