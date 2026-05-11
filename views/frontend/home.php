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

    $escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $eventName = trim((string) ($featuredEvent['label']
        ?? ($featuredEvent['name'] ?? '')));
    $homeTitle = trim((string) ($state['home_title'] ?? 'RSVP'));
    $homeText = trim((string) ($state['home_text'] ?? ''));
?>

<section class="rsvp-section rsvp-hero">
    <?php if ($eventName !== '') { ?>
        <h1 class="rsvp-hero-title"><?=$escape($eventName)?></h1>
    <?php } ?>
    <?php if ($homeTitle !== '' && $homeTitle !== $eventName) { ?>
        <p class="rsvp-hero-subtitle"><?=$escape($homeTitle)?></p>
    <?php } ?>
    <?php if ($homeText !== '') { ?>
        <div class="rsvp-hero-text"><?= nl2br($escape($homeText)) ?></div>
    <?php } ?>
</section>

<section class="rsvp-section rsvp-section-event-date">
    <?php Rsvp::component('event-date'); ?>
</section>

<section class="rsvp-section rsvp-section-countdown">
    <?php Rsvp::component('countdown'); ?>
</section>

<section class="rsvp-section rsvp-section-form">
    <h2 class="rsvp-section-title">Conferma la tua partecipazione</h2>
    <?php Rsvp::component('form'); ?>
</section>
