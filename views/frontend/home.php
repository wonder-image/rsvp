<?php
    /**
     * Vista di default della home RSVP.
     *
     * Pensata per essere sovrascritta dal consumer:
     *     custom/modules/rsvp/views/frontend/home.php
     *
     * Qui dentro componiamo i blocchi riutilizzabili di
     * `views/components/`:
     *   - event-date  → data + ora + venue
     *   - countdown   → countdown live verso la data evento
     *   - form        → form RSVP completo con submit via API_CLIENT
     *
     * Il consumer può:
     *   1. lasciare questa view e sovrascrivere SINGOLI componenti
     *      (es. `custom/modules/rsvp/views/components/form.php`);
     *   2. sostituire questa view con la propria e includere i
     *      componenti col helper `Wonder\Plugin\Rsvp\Rsvp::component('countdown', [...])`.
     */

    use Wonder\Plugin\Rsvp\Rsvp;

    $state = is_array($STATE ?? null) ? $STATE : [];
    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $featuredEvent = is_array($state['featured_event'] ?? null) ? $state['featured_event'] : [];

    $eventName = trim((string) ($featuredEvent['label']
        ?? ($featuredEvent['name'] ?? '')));
    $homeTitle = trim((string) ($state['home_title'] ?? rsvp_trans('pages.rsvp.home.title', 'RSVP')));
    $homeText = trim((string) ($state['home_text'] ?? ''));
?>

<section class="mt-10">
    <div class="content a-c">
        <?php if ($eventName !== '') { ?>
            <h1 class="title-big tx-upper w-100"><?=e($eventName)?></h1>
        <?php } ?>
        <?php if ($homeTitle !== '' && $homeTitle !== $eventName) { ?>
            <p class="subtitle w-100 mt-4"><?=e($homeTitle)?></p>
        <?php } ?>
        <?php if ($homeText !== '') { ?>
            <div class="text w-100 mt-4"><?= nl2br(e($homeText)) ?></div>
        <?php } ?>
    </div>
</section>

<section class="mt-10">
    <div class="content">
        <?php Rsvp::component('event-date'); ?>
    </div>
</section>

<section class="mt-10">
    <div class="content">
        <?php Rsvp::component('countdown'); ?>
    </div>
</section>

<?php Rsvp::component('form'); ?>
