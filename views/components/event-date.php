<?php
    /**
     * Componente: data + ora + location dell'evento.
     *
     * Override consumer:
     *     custom/modules/rsvp/views/components/event-date.php
     *
     * Args attesi in $args (tutti opzionali):
     *   - 'event'     array  Featured event (default: $STATE['featured_event'])
     *   - 'date'      string Override esplicito della data (ISO/strtotime)
     *   - 'location'  string Override esplicito del nome location
     *   - 'address'   string Override esplicito dell'indirizzo
     *   - 'eyebrow'   string Testo piccolo sopra la data (default: '')
     *   - 'time_prefix' string Prefisso prima dell'ora (default: '')
     *
     * Stampa solo se almeno data o location sono valorizzati.
     */

    $args = $args ?? [];
    $event = is_array($args['event'] ?? null)
        ? $args['event']
        : (is_array($STATE['featured_event'] ?? null) ? $STATE['featured_event'] : []);

    $dateRaw = trim((string) ($args['date'] ?? ($event['date'] ?? '')));
    $locationName = trim((string) ($args['location'] ?? ($event['location_name'] ?? '')));
    $locationAddress = trim((string) ($args['address'] ?? ($event['location_address'] ?? '')));
    $eyebrow = (string) ($args['eyebrow'] ?? '');
    $timePrefix = (string) ($args['time_prefix'] ?? '');

    if ($dateRaw === '' && $locationName === '') {
        return;
    }

    $ts = $dateRaw !== '' ? strtotime($dateRaw) : false;
    $italianMonths = [
        1 => 'GENNAIO', 2 => 'FEBBRAIO', 3 => 'MARZO', 4 => 'APRILE',
        5 => 'MAGGIO', 6 => 'GIUGNO', 7 => 'LUGLIO', 8 => 'AGOSTO',
        9 => 'SETTEMBRE', 10 => 'OTTOBRE', 11 => 'NOVEMBRE', 12 => 'DICEMBRE',
    ];

    if ($ts !== false) {
        $dateDay = date('d', $ts);
        $dateMonth = $italianMonths[(int) date('n', $ts)] ?? mb_strtoupper(date('F', $ts));
        $dateYear = date('Y', $ts);
        $dateTime = date('H.i', $ts);
    } else {
        $dateDay = $dateMonth = $dateYear = $dateTime = '';
    }

    $escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<div class="rsvp-event-date">
    <?php if ($eyebrow !== '') { ?>
        <div class="rsvp-event-date-eyebrow"><?=$escape($eyebrow)?></div>
    <?php } ?>

    <?php if ($ts !== false) { ?>
        <div class="rsvp-event-date-date">
            <?=$escape($dateDay)?>
            <span class="rsvp-event-date-month"><?=$escape($dateMonth)?></span>
            <?=$escape($dateYear)?>
        </div>
        <div class="rsvp-event-date-time">
            <?=$timePrefix !== '' ? $escape($timePrefix).' ' : ''?><?=$escape($dateTime)?>
        </div>
    <?php } ?>

    <?php if ($locationName !== '') { ?>
        <div class="rsvp-event-date-venue"><?=$escape($locationName)?></div>
    <?php } ?>
    <?php if ($locationAddress !== '') { ?>
        <div class="rsvp-event-date-address"><?=$escape($locationAddress)?></div>
    <?php } ?>
</div>
