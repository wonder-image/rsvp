<?php

    if (empty($LANG)) { $LANG = 'IT'; }

    $PATH->lang = "$PATH->site/".strtoupper($LANG);
    $file = file_get_contents("$PATH->site/LANG/$LANG.json");
    $TEXT = json_decode($file);