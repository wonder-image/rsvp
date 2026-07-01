<?php

    /**
     * Componente: footer RSVP.
     *
     * Riceve le info dell'evento in evidenza tramite `$args['featured_event']`
     * (passato dal layout `rsvp.main`). I legacy-globals del framework
     * (es. $SOCIETY) restano disponibili perché l'include avviene nello
     * scope corrente tramite `Rsvp::component()`.
     *
     * Override consumer: `custom/modules/rsvp/view/components/footer.php`.
     */

    $args ??= [];
    $featuredEvent = is_array($args['featured_event'] ?? null) ? $args['featured_event'] : [];
    $eventName = trim((string) ($featuredEvent['label'] ?? ($featuredEvent['name'] ?? '')));

?>

<footer class="bg-secondary">
    <div class="content">

        <div class="w-10 w-p-15 c-w">
            <?= __ri($SOCIETY->logoWhite)->size(1200)->skeleton(false)->addClass('w-100')->render() ?>
        </div>

        <?php if ($eventName !== '') { ?>
            <div class="subtitle a-c tx-upper w-100 mt-5"><?= e($eventName) ?></div>
        <?php } ?>

    </div>
</footer>
