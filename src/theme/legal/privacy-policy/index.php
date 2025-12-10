<?php
    
    $FRONTEND = true;
    $PRIVATE = false;
    $PERMIT = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";

    $PAGE_KEY = "privacy_policy";

    $SEO->title = __t("legal.{$PAGE_KEY}.seo.title");
    $SEO->description = __t("legal.{$PAGE_KEY}.seo.description");
    $SEO->url = __u('legal/privacy-policy');

?>
<!DOCTYPE html>
<html lang="<?=__l()?>">
<head>

    <?php include $ROOT_APP.'/utility/frontend/head.php'; ?>

</head>
<body>

    <?php include $ROOT_APP.'/utility/frontend/body-start.php' ?>
    <?php include $ROOT.'/custom/utility/frontend/header.php' ?>
    
    <section class="intro">
        <div class="content">

            <div class="title"> <?=__t("legal.{$PAGE_KEY}.content.title")?> </div>
            <div class="text mt-4"> <?=__t("legal.{$PAGE_KEY}.content.subtitle")?> </div>

            <?php

                foreach (__t("legal.{$PAGE_KEY}.content.paragraphs") as $key => $value) {
                    
                    echo "<div class=\"subtitle mt-10\"> {$value['title']} </div> <div class=\"text mt-4\"> {$value['text']} </div>";

                }

            ?>
            
            <div class="text mt-10"> <?=__t("legal.{$PAGE_KEY}.content.payoff")?> </div>

        </div>
    </section>

    <?php include $ROOT.'/custom/utility/frontend/footer.php' ?>
    <?php include $ROOT_APP.'/utility/frontend/body-end.php' ?>

</body>
</html>