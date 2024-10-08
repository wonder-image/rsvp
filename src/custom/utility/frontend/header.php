<header class="bg-white">
    <div class="content">

        <img src="<?=$PATH->logoIcon?>" alt="Icon <?=$SOCIETY->name?>" class="c-w phone-none" style="height: 50px;">
        <img src="<?=$PATH->logoIcon?>" alt="Icon <?=$SOCIETY->name?>" class="c-h pc-none" style="height: 50px;">

        <div class="p-a bottom c-w nav-list tx-color phone-none" style="line-height: 16px">
            <a href="<?=$PATH->site?>/<?=$LANG?>" class="nav">HOME</a>
            <a href="#rsvp" class="nav">RSVP</a>
        </div>

        <div id="hamburger" class="c-h f-end pc-none" onclick="menuMobile()">
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
            <a href="<?=$PATH->site?>" class="nav">HOME</a>
            <a href="#rsvp" onclick="menuMobile()" class="nav">RSVP</a>
        </div>

    </div>
</section>