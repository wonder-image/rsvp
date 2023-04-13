<?php

    $FRONTEND = true;
    $PRIVATE = false;
    $PERMIT = [];

    $RSVP_PRIVATE = true;
    $RSVP_AUTHORITY = [];

    $ROOT = $_SERVER['DOCUMENT_ROOT'];
    require_once $ROOT."/vendor/wonder-image/app/wonder-image.php";
    require_once $ROOT."/LANG/set-up.php";

?>
<!DOCTYPE html>
<html lang="it">
<head>

    <?php  include $ROOT_APP.'/utility/frontend/head.php'; ?>

</head>
<body>

    <?php include $ROOT_APP.'/utility/frontend/body-start.php' ?>
    <?php include $ROOT.'/custom/utility/frontend/header.php' ?>

    <section class="intro full-page">
        <div class="content">

        </div>
    </section>

    <section id="countdown">
        <div class="content content-little">
            <div class="title a-c">
                <?=$TEXT->title->big_day?>
            </div>
            <div class="w-100 d-grid col-4 gap-4 mt-8">

                <div class="a-c">
                    <div class="title-big">
                        <span class="days"></span>
                    </div>
                    <div class="text mt-2">
                        <?=$TEXT->word->days?>
                    </div>
                </div>

                <div class="a-c">
                    <div class="title-big">
                        <span class="hours"></span>
                    </div>
                    <div class="text mt-2">
                        <?=$TEXT->word->hours?>
                    </div>
                </div>

                <div class="a-c">
                    <div class="title-big">
                        <span class="minutes"></span>
                    </div>
                    <div class="text mt-2">
                        <?=$TEXT->word->minutes?>
                    </div>
                </div>

                <div class="a-c">
                    <div class="title-big">
                        <span class="seconds"></span>
                    </div>
                    <div class="text mt-2">
                        <?=$TEXT->word->seconds?>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script>

        var countDownDate = new Date("<?=$EVENT->date?>").getTime();
        
        var x = setInterval(function() {

            var now = new Date().getTime();
            var distance = countDownDate - now;

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            if (distance < 0) {
                clearInterval(x);
                days = 00;
                hours = 00;
                minutes = 00;
                seconds = 00;
            }

            document.querySelector("#countdown .days").innerHTML = days;
            document.querySelector("#countdown .hours").innerHTML = hours;
            document.querySelector("#countdown .minutes").innerHTML = minutes;
            document.querySelector("#countdown .seconds").innerHTML = seconds;

        }, 1000);

    </script>
    
    <?php include $ROOT.'/custom/utility/frontend/footer.php' ?>
    <?php include $ROOT_APP.'/utility/frontend/body-end.php' ?>
    
</body>
</html>