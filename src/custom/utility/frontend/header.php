<header class="bg-white">
    <div class="content">

        <div class="w-100"  style="height: 50px;">
            <a href="<?=__u()?>" class="c-w"  style="width: 50px;height: 50px;">
                <?=__ri($SOCIETY->icon)
                        ->alt("Icona $SOCIETY->name")
                        ->fitContain()
                        ->skeleton(false)
                        ->size(480)
                        ->render()?>
            </a>
        </div>

        <div class="w-100">
            <div class="center phone-none">
                <div class="d-flex tx-white gap-4 tx-uppe j-content-center" style="line-height: 16px">
                <a href="<?=__u()?>" class="tx-none"> <?=__t("components.navigation.home")?> </a>
                <a href="#rsvp" class="tx-none"> <?=__t("components.navigation.rsvp")?> </a>
            </div>
        </div>

        <div id="hamburger" class="c-h f-end pc-none tablet-none" onclick="menuMobile()">
            <div class="bar bar-1 bg-primary"></div>
            <div class="bar bar-2 bg-primary"></div>
            <div class="bar bar-3 bg-primary"></div>
            <div class="bar bar-4 bg-primary"></div>
            <div class="bar bar-5 bg-primary"></div>
        </div>

    </div>
</header>

<section id="nav-mobile">
    <div class="bg" onclick="menuMobile()"></div>
    <div class="content bg-white">

        <div class="nav-list">
            <a href="<?=__u()?>" class="nav"> <?=__t("components.navigation.home")?> </a>
            <a href="#rsvp" class="nav" onclick="menuMobile()"> <?=__t("components.navigation.rsvp")?> </a>
        </div>

    </div>
</section>