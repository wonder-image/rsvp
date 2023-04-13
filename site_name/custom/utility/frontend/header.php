<header class="bg-white">
    <div class="content">

        <img src="<?=$PATH->logoIcon?>" alt="Icon <?=$SOCIETY->name?>" class="c-w" style="height: 50px;">

        <div class="p-a bottom c-w nav-list tx-color phone-none" style="line-height: calc((var(--header-height) - (var(--spacer) * 8)) - 50px)">
            <a href="<?=$PATH->site?>/<?=$LANG?>" class="nav">Home</a>
        </div>

        <div id="hamburger" class="c-h f-end pc-none" onclick="menuMobile()">
            <div class="bar bar-1 bg-white"></div>
            <div class="bar bar-2 bg-white"></div>
            <div class="bar bar-3 bg-white"></div>
            <div class="bar bar-4 bg-white"></div>
            <div class="bar bar-5 bg-white"></div>
        </div>

    </div>
</header>

<section id="nav-mobile">
    <div class="bg" onclick="menuMobile()"></div>
    <div class="content bg-white">

        <div class="nav-list">
            <a href="<?=$PATH->site?>" class="nav">Home</a>
        </div>

    </div>
</section>