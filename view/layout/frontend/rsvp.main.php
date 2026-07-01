<?php

    /**
     * Layout: pagina principale RSVP.
     *
     * Chaina su `frontend.base`, stampa il body della pagina (`$PAGE_CONTENT`)
     * e richiama il footer del modulo passandogli le info dell'evento in
     * evidenza ricavate da `$STATE['featured_event']`.
     */

    \Wonder\View\View::layout('frontend.base');

    $featuredEvent = \Wonder\Plugin\Rsvp\Rsvp::context()['featured_event'] ?? [];

?>

    <?= $PAGE_CONTENT ?>

    <?php \Wonder\Plugin\Rsvp\Rsvp::component('footer', ['featured_event' => $featuredEvent]); ?>

<?php \Wonder\View\View::end(); ?>
