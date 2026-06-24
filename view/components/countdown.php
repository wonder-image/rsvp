<?php
    /**
     * Componente: countdown live verso una data target.
     *
     * Override consumer:
     *     custom/modules/rsvp/view/components/countdown.php
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

    $args = props($args ?? [], [
        'target_date' => $STATE['featured_event']['date'] ?? '',
        'labels' => [],
    ]);
    $targetDate = trim((string) $args['target_date']);

    $labels = is_array($args['labels'] ?? null) ? $args['labels'] : [];
    $labels = array_merge([
        'days' => __t('pages.rsvp.countdown.days'),
        'hours' => __t('pages.rsvp.countdown.hours'),
        'minutes' => __t('pages.rsvp.countdown.minutes'),
        'seconds' => __t('pages.rsvp.countdown.seconds'),
    ], $labels);

    $ts = $targetDate !== '' ? strtotime($targetDate) : false;
    if ($ts === false) {
        return;
    }

    $iso = date('c', $ts);
?>

<div class="w-100 d-grid col-4 gap-5 a-c" data-rsvp-countdown="<?=e($iso)?>">
    <?php foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) { ?>
        <div class="w-100">
            <div class="title-big fw-700" data-unit="<?=e($unit)?>">00</div>
            <div class="text-small tx-upper"><?=e((string) $labels[$unit])?></div>
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
