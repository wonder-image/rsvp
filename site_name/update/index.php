<?php

    $PRIVATE = false;
    $PERMIT = [];

    $FRONTEND = true;

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    require_once $ROOT."/update/table/index.php";
    require_once $ROOT."/update/row/index.php";
    require_once $ROOT."/update/page/index.php";
    
?>