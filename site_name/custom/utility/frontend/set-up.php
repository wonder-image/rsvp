<?php

    $EVENT = info('details', 'id', '1');
    $EVENT->datePretty = date('d.m.Y', strtotime($EVENT->date));

?>