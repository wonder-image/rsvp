<?php
    /**
     * Componente: data + ora + location dell'evento.
     *
     * Override consumer:
     *     custom/view/components/rsvp/event-date.php
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

    $args = props($args ?? [], ['state' => $STATE ?? []]);
    $event = is_array($args['event'] ?? null)
        ? $args['event']
        : (is_array($STATE['featured_event'] ?? null) ? $STATE['featured_event'] : []);

    $dateRaw = trim((string) ($args['date'] ?? ($event['date'] ?? '')));
    $locationName = trim((string) ($args['location'] ?? ($event['location_name'] ?? '')));
    $locationAddress = trim((string) ($args['address'] ?? ($event['location_address'] ?? '')));
    $eyebrow = (string) ($args['eyebrow'] ?? __t('pages.rsvp.event_date.eyebrow'));
    $timePrefix = (string) ($args['time_prefix'] ?? __t('pages.rsvp.event_date.time_prefix'));

    if ($dateRaw === '' && $locationName === '') {
        return;
    }

    $ts = $dateRaw !== '' ? strtotime($dateRaw) : false;
    if ($ts !== false) {
        $dateDay = rsvpDatePart($dateRaw, 'day_number');
        $dateMonth = mb_strtoupper(rsvpDatePart($dateRaw, 'month'));
        $dateYear = date('Y', $ts);
        $dateTime = rsvpDatePart($dateRaw, 'time');
    } else {
        $dateDay = $dateMonth = $dateYear = $dateTime = '';
    }

?>

<div class="w-100 a-c">
    <?php if ($eyebrow !== '') { ?>
        <div class="text-small tx-upper"><?=e($eyebrow)?></div>
    <?php } ?>

    <?php if ($ts !== false) { ?>
        <div class="title-big fw-700 mt-2">
            <?=e($dateDay)?> <?=e($dateMonth)?> <?=e($dateYear)?>
        </div>
        <div class="text mt-1">
            <?=$timePrefix !== '' ? e($timePrefix).' ' : ''?><?=e($dateTime)?>
        </div>
    <?php } ?>

    <?php if ($locationName !== '') { ?>
        <div class="subtitle fw-500 mt-4"><?=e($locationName)?></div>
    <?php } ?>
    <?php if ($locationAddress !== '') { ?>
        <div class="text mt-1"><?=e($locationAddress)?></div>
    <?php } ?>
</div>
