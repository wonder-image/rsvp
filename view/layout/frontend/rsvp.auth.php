<?php

    /**
     * Layout: pagina di autenticazione RSVP.
     *
     * Fornisce la "chrome" della pagina auth (sezione full-page, texture di
     * sfondo e card centrata con logo) e slotta il body della pagina in
     * `$PAGE_CONTENT`. Titolo, sottotitolo e id/onsubmit del form sono
     * configurabili passando le variabili come secondo argomento di
     * `View::layout(...)`.
     *
     * Variabili supportate:
     *   - $id        string  id del form (default: 'rsvp-auth-form')
     *   - $title     string  titolo (subtitle) della card
     *   - $subtitle  string  testo descrittivo sotto il titolo
     *   - $texture   string  nome file texture in upload/images (default: 'texture.png')
     *   - $method    string  metodo del form (default: 'post')
     *   - $action    string  action del form (default: '')
     *   - $onsubmit  string  handler onsubmit del form (default: '')
     */

    $id ??= 'rsvp-auth-form';
    $title ??= null;
    $subtitle ??= null;
    $texture ??= 'texture.png';
    $method ??= 'post';
    $action ??= '';
    $onsubmit ??= '';

    \Wonder\View\View::layout('frontend.base');

?>

    <section class="full-page">

        <?= __ri($PATH->upload.'/images/'.$texture)->fitCover()->skeleton(false)->size(1920)->render() ?>

        <div class="content content-small" style="z-index: 2;">
            <form
                id="<?= e($id) ?>"
                method="<?= e($method) ?>"
                action="<?= e($action) ?>"
                autocomplete="off"
                class="w-100 p-6 center bg-secondary b-r-25"
                <?php if ($onsubmit !== '') { ?>onsubmit="<?= $onsubmit ?>"<?php } ?>
            >
                <img src="<?= $SOCIETY->logoWhite ?>" alt="<?= e($SOCIETY->name) ?>" class="w-30 c-w">

                <?php if (!empty($title)) { ?>
                    <div class="subtitle a-c tx-upper w-100 mt-6"><?= $title ?></div>
                <?php } ?>
                <?php if (!empty($subtitle)) { ?>
                    <div class="text a-c w-100 mt-2"><?= $subtitle ?></div>
                <?php } ?>

                <?= $PAGE_CONTENT ?>

            </form>
        </div>
    </section>

<?php \Wonder\View\View::end(); ?>
