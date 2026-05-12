<?php
    /**
     * Componente: countdown live verso una data target.
     *
     * Override consumer:
     *     custom/modules/rsvp/views/components/countdown.php
     *
     * Args attesi in $args:
     *   - 'target_date' string  ISO date / strtotime-parsable. Obbligatorio.
     *                            Fallback: $STATE['featured_event']['date'].
     *   - 'labels'      array   Override etichette (chiavi: days|hours|minutes|seconds).
     *                            Default IT.
     *
     * Se la data non è valorizzata o non parsabile, il componente non emette nulla.
     *
     * Lo script JS è idempotente: se più countdown coesistono sulla pagina,
     * inizializza solo quelli non ancora processati (data-rsvp-countdown-init).
     */

    $args = $args ?? [];
    $targetDate = trim((string) ($args['target_date']
        ?? ($STATE['featured_event']['date'] ?? '')));

    $labels = is_array($args['labels'] ?? null) ? $args['labels'] : [];
    $labels = array_merge([
        'days' => rsvp_trans('rsvp.frontend.countdown.days', 'Giorni'),
        'hours' => rsvp_trans('rsvp.frontend.countdown.hours', 'Ore'),
        'minutes' => rsvp_trans('rsvp.frontend.countdown.minutes', 'Minuti'),
        'seconds' => rsvp_trans('rsvp.frontend.countdown.seconds', 'Secondi'),
    ], $labels);

    $ts = $targetDate !== '' ? strtotime($targetDate) : false;
    if ($ts === false) {
        return;
    }

    $iso = date('c', $ts);
    $escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<div class="rsvp-countdown" data-rsvp-countdown="<?=$escape($iso)?>">
    <?php foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) { ?>
        <div class="rsvp-countdown-cell">
            <div class="rsvp-countdown-num" data-unit="<?=$escape($unit)?>">00</div>
            <div class="rsvp-countdown-label"><?=$escape((string) $labels[$unit])?></div>
        </div>
    <?php } ?>
</div>

<script>
    (() => {
        document.querySelectorAll('[data-rsvp-countdown]').forEach((cd) => {
            if (cd.dataset.rsvpCountdownInit) return;
            cd.dataset.rsvpCountdownInit = '1';

            const target = new Date(cd.dataset.rsvpCountdown).getTime();
            const tick = () => {
                const distance = Math.max(0, target - Date.now());
                const v = {
                    days: Math.floor(distance / 86400000),
                    hours: Math.floor((distance % 86400000) / 3600000),
                    minutes: Math.floor((distance % 3600000) / 60000),
                    seconds: Math.floor((distance % 60000) / 1000),
                };
                Object.entries(v).forEach(([unit, value]) => {
                    const node = cd.querySelector(`[data-unit="${unit}"]`);
                    if (node) node.textContent = String(value).padStart(2, '0');
                });
            };
            tick();
            setInterval(tick, 1000);
        });
    })();
</script>
