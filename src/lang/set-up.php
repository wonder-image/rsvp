<?php

    if (empty($LANG)) { $LANG = 'IT'; }

    $LANG = strtolower($LANG);

    $file = file_get_contents($PATH->site.'/lang/'.strtoupper($LANG).'.json');
    $TEXT = json_decode($file);