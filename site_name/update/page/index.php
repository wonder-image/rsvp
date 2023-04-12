<?php

    $PRIVATE = false;
    $PERMIT = [];

    $FRONTEND = true;

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    $files = empty(scandir("$ROOT_APP/build/page/")) ? [] : scandir("$ROOT_APP/build/page/");

    foreach ($files as $file) {
        if ($file != '' && $file != '.' && $file != '..') {
            include "$ROOT_APP/build/page/$file";
        }
    }

    $files = empty(scandir("$ROOT/custom/build/page/")) ? [] : scandir("$ROOT/custom/build/page/");

    foreach ($files as $file) {
        if ($file != '' && $file != '.' && $file != '..') {
            include "$ROOT/custom/build/page/$file";
        }
    }
    
?>