<?php

    $RSVP_PRIVATE = false;
    $RSVP_AUTHORITY = [];
    
    $FRONTEND = true;
    $PRIVATE = false;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    $PAGE_KEY = 'login';

    $SEO->url = __u($PAGE_KEY);
    $SEO->breadcrumb = [
        $SEO->url => __t("components.navigation.$PAGE_KEY")
    ];

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
<html lang="<?=__l()?>">
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
                    <?=password(__t('components.forms.fields.password.label'), 'password', '', 'required')?>
                </div>
                <div class="w-100 mt-4">
                    <?=submit(__t('components.buttons.login'), "login", "btn-primary c-w")?>
                </div>
            </form>
        </div>
    </section>

    <?php include $ROOT_APP.'/utility/frontend/body-end.php' ?>
    
</body>
</html>