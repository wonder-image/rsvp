<?php

    $BACKEND = true;
    $PRIVATE = true;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    require_once "set-up.php";

    $FILE_NAME = "Partecipanti-".date('Y-m-d-H-i');
    $ARRAY = [["Nome", "Cognome", "Partecipanti", "Email", "Telefono", "Eventi", "Allergie", "Richieste", "Consenso Privacy", "Consenso Foto"]];

    $SQL = sqlSelect('rsvp', ['deleted' => 'false']);

    foreach ($SQL->row as $key => $row) {

        $PARTECIPATION = info('rsvp', 'id', $row['id']);

        // $EVENT = is_array(json_decode($PARTECIPATION->events)) ? json_decode($PARTECIPATION->events) : [ $PARTECIPATION->events ];

        // $eventi = [];
        // foreach ($EVENT as $key => $value) { array_push($eventi, $EVENTI[$value]); }
        // $eventi = implode(', ', $eventi);

        $privacy = json_decode($PARTECIPATION->privacy)[0] == true ? 'Accettato' : 'Rifiutato';
        $photo = json_decode($PARTECIPATION->photo)[0] == true ? 'Accettato' : 'Rifiutato';

        $partecipantName = json_decode($PARTECIPATION->name);
        $partecipantSurname = json_decode($PARTECIPATION->surname);

        foreach ($partecipantName as $key => $name) {
                
            $surname = $partecipantSurname[$key];
            
            array_push($ARRAY, [
                $name,
                $surname,
                $PARTECIPATION->participants,
                $PARTECIPATION->email,
                $PARTECIPATION->phone,
                $eventi ?? '',
                $PARTECIPATION->allergies,
                $PARTECIPATION->requests,
                $privacy,
                $photo
            ]);

            break;
            
        }

    }

    switch ($_GET['file']) {
        case 'csv':
            arrayToCsv($ARRAY, $FILE_NAME);
            break;
        case 'xls':
            arrayToXls($ARRAY, $FILE_NAME);
            break;
    }