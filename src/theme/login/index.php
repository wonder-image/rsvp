<?php

    $RSVP_PRIVATE = false;
    $RSVP_AUTHORITY = [];
    
    require_once __DIR__."/../set-up.php";

    if (isset($_POST['login'])) {
        
        $password = sanitize($_POST['password']);

        $SQL = sqlSelect('rsvp_password', ['password' => $password], 1);

        if ($SQL->exists && $SQL->row['password'] === $password) {
            if ($SQL->row['deleted'] == 'false') {
                if ($SQL->row['active'] == 'true') {

                    $_SESSION['password_id'] = $SQL->row['id'];
                    header("Location: ../");

                } else {
                    $ALERT = 909; 
                }
            } else {
                $ALERT = 912; 
            }
            
        } else {
            $ALERT = 905; 
        }
        
    }

?>
<!DOCTYPE html>
<html lang="<?=$LANG?>">
<head>

    <?php include $ROOT_APP.'/utility/frontend/head.php'; ?>

</head>
<body>

    <?php include $ROOT_APP.'/utility/frontend/body-start.php' ?>

    <section class="full-page bg-primary">
        <div class="content content-small">
            <form action="" method="post" class="w-100 p-6 center bg-white">
                <img src="<?=$PATH->logoIcon?>" alt="Icon <?=$SOCIETY->name?>" alt="" class="w-30 c-w">
                <div class="w-100 mt-6">
                    <?=password($TEXT->form->password, 'password', '', 'required')?>
                </div>
                <div class="w-100 mt-4">
                    <?=submit($TEXT->form->log_in, "login", "btn-primary c-w")?>
                </div>
            </form>
        </div>
    </section>

    <?php include $ROOT_APP.'/utility/frontend/body-end.php' ?>
    
</body>
</html>