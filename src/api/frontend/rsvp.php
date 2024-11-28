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

        $nameList = "";

        if (is_array($FORM->name)) {

            foreach ($FORM->name as $key => $v) { 
                $nameList .= '<li>Partecipante '.$key + 1 .': '.$v.' '.$FORM->surname[$key].'</li>'; 
            }

        } else {

            $nameList = '<li>Nome: '.$VALUES['name'].' '.$VALUES['name'].'</li>'; 
            
        }

        $BODY = "Nuova conferma di partecipazione per l'evento di <b>$EVENT->name</b>. Ecco i dati dei partecipanti:<br>
        <ul>
            $nameList
            <li>Email: {$VALUES['email']}</li>
            <li>Cellulare: {$VALUES['cel']}</li>
            <li>Intolleranze:<br>{$VALUES['allergies']}</li>
        </ul>
        <a href='$PATH->site/backend/rsvp/response/list.php'>Clicca qui</a> per vedere tutte le partecipazioni!";


        $BODY_CLIENT = "Buongiorno,<br>
        $SOCIETY->name ti aspetta al <b>$EVENT->name</b> il $EVENT->datePretty in <a href='$SOCIETY->gmaps'>$SOCIETY->address</a>";
       
        if (sendMail($VALUES['email'], $SOCIETY->email, 'Nuova conferma di partecipazione', $BODY)) {
            sendMail($SOCIETY->email, $VALUES['email'], 'Conferma di partecipazione', $BODY_CLIENT);
        }
    
    }