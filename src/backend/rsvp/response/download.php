<?php

    $BACKEND = true;
    $PRIVATE = true;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    require_once "set-up.php";

    $FILE_NAME = "Partecipanti-".date('Y-m-d-H-i');
    $ARRAY = [["Nome", "Cognome", "Partecipanti", "Email", "Cel", "Eventi", "Allergie", "Richieste", "Consenso Privacy", "Consenso Foto"]];

    $SQL = sqlSelect('rsvp', ['deleted' => 'false']);

    foreach ($SQL->row as $key => $row) {

        $PARTECIPATION = info('rsvp', 'id', $row['id']);

        $EVENT = is_array(json_decode($PARTECIPATION->events)) ? json_decode($PARTECIPATION->events) : [ $PARTECIPATION->events ];

        $eventi = [];
        foreach ($EVENT as $key => $value) { array_push($eventi, $EVENTI[$value]); }
        $eventi = implode(', ', $eventi);

        $privacy = json_decode($PARTECIPATION->privacy)[0] == true ? 'Accettato' : 'Rifiutato';
        $photo = json_decode($PARTECIPATION->photo)[0] == true ? 'Accettato' : 'Rifiutato';

        array_push($ARRAY, [
            $PARTECIPATION->name,
            $PARTECIPATION->surname,
            $PARTECIPATION->participants,
            $PARTECIPATION->email,
            $PARTECIPATION->cel,
            $eventi,
            $PARTECIPATION->allergies,
            $PARTECIPATION->requests,
            $privacy,
            $photo
        ]);

    }

    switch ($_GET['file']) {
        case 'csv':
            arrayToCsv($ARRAY, $FILE_NAME);
            break;
        case 'xls':
            arrayToXls($ARRAY, $FILE_NAME);
            break;
    }