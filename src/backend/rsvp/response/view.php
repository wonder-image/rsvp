<?php

    $BACKEND = true;
    $PRIVATE = true;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    require_once "set-up.php";

    if (!empty($PAGE->redirect)) {
        $REDIRECT = $PAGE->redirect;
    } else {
        $REDIRECT = "$PATH->backend/$NAME->folder/list.php";
    }

    $PARTECIPATION = info('rsvp', 'id', $_GET['id']);
    $PARTECIPATION->prettyCreation = date('d/m/Y', strtotime($PARTECIPATION->creation)).' alle '.date('H:i', strtotime($PARTECIPATION->creation));
    
?>
<!DOCTYPE html>
<html lang="it">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partecipazione di <?=$PARTECIPATION->name.' '.$PARTECIPATION->surname?></title>

    <?php include $ROOT_APP."/utility/backend/head.php"; ?>

</head>
<body>
    
    <?php include $ROOT_APP."/utility/backend/body-start.php"; ?>
    <?php include $ROOT_APP."/utility/backend/header.php" ?>

    <div class="row g-3">

        <wi-card class="col-12">
            <h3><a href="<?=$REDIRECT?>" type="button" class="text-dark"><i class="bi bi-arrow-left-short"></i></a> Partecipazione di <?=$PARTECIPATION->name.' '.$PARTECIPATION->surname?></h3>
            <h6>Effettuata il <?=$PARTECIPATION->prettyCreation?></h6>
        </wi-card>

        <div class="col-9">
            <div class="row g-3">

                <wi-card class="col-12">
                    <div class="col-6">
                        <h6>DATI</h6>
                        <div class="w-100 mt-2">
                            Nome: <b><?=$PARTECIPATION->name?> <?=$PARTECIPATION->surname?></b> <br>
                            Email: <b><?=$PARTECIPATION->email?></b> <br>
                            Cel: <b><?=$PARTECIPATION->cel?></b>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6>PARTECIPANTI</h6>
                        <div class="w-100 mt-2">
                            Partecipanti: <b><?=$PARTECIPATION->participants?></b>
                        </div>
                    </div>
                </wi-card>

                <wi-card class="col-6">
                    <div class="col-12">
                        <h6>ALLERGIE</h6>
                        <div class="w-100 mt-2"><?=$PARTECIPATION->allergies?></div>
                    </div>
                </wi-card>

                <wi-card class="col-6">
                    <div class="col-12">
                        <h6>RICHIESTE</h6>
                        <div class="w-100 mt-2"><?=$PARTECIPATION->requests?></div>
                    </div>
                </wi-card>

            </div>
        </div>

        <div class="col-3">
            <div class="row g-3">

                <wi-card class="col-12">
                    <div class="col-12">
                        <h6>DETTAGLI</h6>
                        <div class="w-100 mt-2">
                            Creazione: <b><?=$PARTECIPATION->prettyCreation?></b> <br>
                            Lingua: <b><?=$PARTECIPATION->lang?></b> <br>
                            Password: <b><?=empty($PARTECIPATION->password_id) ? "No" : sqlSelect('rsvp_password', ['id' => $PARTECIPATION->password_id], 1)->row['password']?></b>
                        </div>
                    </div>
                </wi-card>

                <wi-card class="col-12">
                    <div class="col-12">
                        <h6>EVENTI</h6>
                        <div class="w-100 mt-2">
                            <?php

                                $EVENT = json_decode($PARTECIPATION->events);
                                foreach ($EVENT as $key => $value) {
                                   echo $EVENTI[$value].'<br>';
                                }

                            ?>
                        </div>
                    </div>
                </wi-card>

            </div>
        </div>
    
    </div>

    <?php include $ROOT_APP."/utility/backend/footer.php" ?>
    <?php include $ROOT_APP."/utility/backend/body-end.php"; ?>

</body>
</html>