<?php
    $state = is_array($STATE ?? null) ? $STATE : [];
    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $pageContent = is_array($state['page_content'] ?? null) ? $state['page_content'] : [];
    $visibleEvents = is_array($state['visible_events'] ?? null) ? $state['visible_events'] : [];
    $featuredEvent = is_array($state['featured_event'] ?? null) ? $state['featured_event'] : [];
    $requiresInviteCode = !empty($state['requires_invite_code']);
    $hasSession = (int) ($session['id'] ?? 0) > 0;
    $canAccessForm = !$requiresInviteCode || $hasSession;
    $locale = (string) ($state['locale'] ?? (function_exists('__l') ? __l() : 'it'));
    $maxParticipants = max(1, (int) ($state['max_participants'] ?? 1));
    $allowChildren = !empty($state['allow_children']);
    $maxChildren = max(0, (int) ($state['max_children'] ?? 0));
    $submitUrl = function_exists('__r') ? __r('api.rsvp.submit') : '/api/rsvp/';
    $loginUrl = function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/';
    $privacyField = function_exists('inputAcceptDocument')
        ? inputAcceptDocument('privacy_policy', 'required')
        : '';
    $imageField = (!empty($state['require_image_release']) && function_exists('inputAcceptDocument'))
        ? inputAcceptDocument('image_release', 'required')
        : '';
    $customFields = is_array($state['custom_fields'] ?? null) ? $state['custom_fields'] : [];

    $renderCustomField = static function (string $key, array $def): string {
        $type = (string) ($def['type'] ?? 'text');
        $label = (string) ($def['label'] ?? $key);
        $value = $def['value'] ?? null;
        $attr = !empty($def['required']) ? 'required' : '';
        $options = is_array($def['options'] ?? null) ? $def['options'] : [];

        switch ($type) {
            case 'email':
                return email($label, $key, $value, $attr);
            case 'phone':
                return phone($label, $key, $value, $attr);
            case 'number':
                return number($label, $key, $value, $attr);
            case 'textarea':
                return textarea($label, $key, $value, $attr);
            case 'select':
                return select($label, $key, $options, $value, $attr);
            case 'checkbox':
                return checkbox($label, $key, $options);
            case 'text':
            default:
                return text($label, $key, $value, $attr);
        }
    };

    $ambient = is_array($pageContent['ambient_background'] ?? null) ? $pageContent['ambient_background'] : [];
    $intro = is_array($pageContent['intro'] ?? null) ? $pageContent['intro'] : [];
    $messageSection = is_array($pageContent['message_section'] ?? null) ? $pageContent['message_section'] : [];
    $dateSection = is_array($pageContent['date_section'] ?? null) ? $pageContent['date_section'] : [];
    $locationSection = is_array($pageContent['location_section'] ?? null) ? $pageContent['location_section'] : [];
    $countdown = is_array($pageContent['countdown'] ?? null) ? $pageContent['countdown'] : [];
    $loginContent = is_array($pageContent['login'] ?? null) ? $pageContent['login'] : [];
    $formContent = is_array($pageContent['form'] ?? null) ? $pageContent['form'] : [];

    $pathObject = $PATH ?? null;
    $defaultLogoPath = is_object($pathObject) && isset($pathObject->logo) ? (string) $pathObject->logo : '';

    $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $isTrue = static function (mixed $value): bool {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    };
    $valueFrom = static function (array $source, array $keys, mixed $default = ''): mixed {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $source)) {
                continue;
            }

            $value = $source[$key];

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return $default;
    };
    $renderVideo = static function (string $path) use ($escape): string {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (function_exists('__videoBG')) {
            return (string) __videoBG($path);
        }

        return '<video class="rsvp-video-fallback" autoplay muted loop playsinline>'
            .'<source src="'.$escape($path).'">'
            .'</video>';
    };

    $eventLabel = (string) $valueFrom($featuredEvent, ['label', 'title', 'name'], '');
    $eventDate = (string) $valueFrom($featuredEvent, ['date', 'starts_at', 'event_starts_at'], '');
    $locationName = (string) $valueFrom($featuredEvent, ['location_name', 'position', 'name'], '');
    $locationAddress = (string) $valueFrom($featuredEvent, ['location_address', 'address'], $locationName);
    $locationAddressUrl = (string) $valueFrom($featuredEvent, ['location_address_url', 'urlAddress', 'location_url'], '');
    $locationPositionUrl = (string) $valueFrom($featuredEvent, ['location_position_url', 'urlPosition', 'location_url'], $locationAddressUrl);
    $locationLogo = (string) $valueFrom($featuredEvent, ['location_logo', 'location_logo_url', 'urlLogo'], '');
    $introLogo = trim((string) ($intro['logo_path'] ?? '')) !== ''
        ? (string) $intro['logo_path']
        : $defaultLogoPath;

    $claimText = trim((string) ($messageSection['content'] ?? ($intro['claim'] ?? '')));
    $countdownDate = $eventDate !== '' && strtotime($eventDate) !== false ? date('c', strtotime($eventDate)) : '';

    $ui = [
        'participantLabel' => (string) ($formContent['participant_label'] ?? 'Partecipante'),
        'childLabel' => (string) ($formContent['child_label'] ?? 'Bambino'),
        'participantNameLabel' => (string) ($formContent['participant_name_label'] ?? 'Nome'),
        'participantSurnameLabel' => (string) ($formContent['participant_surname_label'] ?? 'Cognome'),
        'participantDietaryLabel' => (string) ($formContent['participant_dietary_label'] ?? 'Esigenze alimentari'),
        'sendingText' => (string) ($formContent['sending_text'] ?? 'Invio in corso...'),
        'successText' => (string) ($formContent['success_text'] ?? 'Conferma inviata con successo.'),
        'errorText' => (string) ($formContent['error_text'] ?? 'Invio non riuscito.'),
        'eventsRequiredText' => (string) ($formContent['events_required_text'] ?? 'Seleziona almeno un evento.'),
    ];
?>

<style>
    #rsvp-module { position: relative; background: #000; color: #fff; overflow: hidden; }
    #rsvp-module .rsvp-video-fallback { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    #rsvp-module .rsvp-ambient,
    #rsvp-module .rsvp-intro { position: relative; overflow: hidden; }
    #rsvp-module .rsvp-ambient { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
    #rsvp-module .rsvp-layer,
    #rsvp-module .rsvp-gradient { position: absolute; inset: 0; }
    #rsvp-module .rsvp-layer { background: rgba(0, 0, 0, .5); backdrop-filter: blur(10px); }
    #rsvp-module .rsvp-gradient-top { background: linear-gradient(transparent 70%, #000000); }
    #rsvp-module .rsvp-gradient-mobile { background: linear-gradient(transparent 40%, #000000); display: none; }
    #rsvp-module .rsvp-section { position: relative; z-index: 1; padding: 5rem 1.25rem; }
    #rsvp-module .rsvp-full-height { min-height: 100vh; }
    #rsvp-module .rsvp-content { width: min(1100px, calc(100vw - 2.5rem)); margin: 0 auto; position: relative; }
    #rsvp-module .rsvp-content-narrow { width: min(760px, calc(100vw - 2.5rem)); }
    #rsvp-module .rsvp-intro-shell { min-height: min(100vh, 960px); display: flex; align-items: center; justify-content: center; }
    #rsvp-module .rsvp-logo-wrap { width: min(30vw, 360px); aspect-ratio: 1 / 1; position: relative; margin: 0 auto; }
    #rsvp-module .rsvp-logo { width: 100%; height: 100%; object-fit: contain; display: block; }
    #rsvp-module .rsvp-title { font-size: clamp(2.1rem, 4vw, 4.4rem); line-height: 1; text-align: center; }
    #rsvp-module .rsvp-subtitle { font-size: clamp(1.1rem, 2vw, 1.5rem); text-transform: uppercase; letter-spacing: .18em; text-align: center; color: #d8b36f; }
    #rsvp-module .rsvp-date-grid { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; margin-top: 1rem; }
    #rsvp-module .rsvp-date-side { font-size: clamp(1.2rem, 2vw, 1.7rem); }
    #rsvp-module .rsvp-date-day { font-size: clamp(4rem, 10vw, 8rem); line-height: .9; text-align: center; }
    #rsvp-module .rsvp-date-time { margin-top: 2rem; text-align: center; font-size: 1rem; }
    #rsvp-module .rsvp-location-logo { width: min(100%, 440px); display: block; margin: 0 auto; }
    #rsvp-module .rsvp-location-address { margin-top: 2rem; font-size: clamp(1.1rem, 2vw, 1.5rem); text-align: center; }
    #rsvp-module .rsvp-location-address a { color: inherit; text-decoration: none; }
    #rsvp-module .rsvp-countdown-panel { position: relative; overflow: hidden; border-radius: 20px; padding: 2.5rem 1.5rem; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); }
    #rsvp-module .rsvp-countdown-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-top: 2.5rem; }
    #rsvp-module .rsvp-countdown-number { font-size: clamp(2.8rem, 6vw, 5rem); line-height: 1; }
    #rsvp-module .rsvp-countdown-line { width: 48px; height: 2px; background: #d8b36f; margin: .8rem auto; }
    #rsvp-module .rsvp-countdown-label { font-size: 1rem; text-transform: uppercase; letter-spacing: .12em; }
    #rsvp-module .rsvp-panel-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 1.5rem; }
    #rsvp-module .rsvp-panel { background: rgba(255,255,255,.92); color: #241a14; border-radius: 24px; border: 1px solid rgba(216, 179, 111, .22); box-shadow: 0 24px 80px rgba(0,0,0,.24); padding: 2rem; }
    #rsvp-module .rsvp-kicker { font-size: .76rem; letter-spacing: .18em; text-transform: uppercase; color: #9c6b3f; }
    #rsvp-module .rsvp-panel-title { font-size: clamp(1.8rem, 3vw, 3rem); line-height: .95; margin-top: .75rem; }
    #rsvp-module .rsvp-panel-text { margin-top: 1rem; color: #5b493d; }
    #rsvp-module .rsvp-meta { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
    #rsvp-module .rsvp-pill { border-radius: 999px; padding: .55rem .9rem; background: #f1e5d8; color: #614a38; font-size: .92rem; }
    #rsvp-module .rsvp-form { display: grid; gap: 1rem; }
    #rsvp-module .rsvp-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    #rsvp-module .rsvp-label { display: block; margin-bottom: .35rem; font-size: .92rem; color: #503e33; }
    #rsvp-module .rsvp-input,
    #rsvp-module .rsvp-textarea,
    #rsvp-module .rsvp-select { width: 100%; padding: .95rem 1rem; border-radius: 14px; border: 1px solid #d8c4b3; background: #fffdfb; font: inherit; }
    #rsvp-module .rsvp-textarea { min-height: 110px; resize: vertical; }
    #rsvp-module .rsvp-participant { border: 1px solid #ead9ca; border-radius: 18px; padding: 1rem; background: #fffaf5; }
    #rsvp-module .rsvp-participant-title { font-weight: 700; color: #2f241d; margin-bottom: .8rem; }
    #rsvp-module .rsvp-events { display: grid; gap: .65rem; margin-top: .5rem; }
    #rsvp-module .rsvp-checkbox { display: flex; gap: .65rem; align-items: flex-start; }
    #rsvp-module .rsvp-actions { display: flex; flex-wrap: wrap; gap: .75rem; }
    #rsvp-module .rsvp-btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 999px; padding: .95rem 1.4rem; background: #241a14; color: #fff; text-decoration: none; font-weight: 700; cursor: pointer; }
    #rsvp-module .rsvp-btn-secondary { background: #ede0d3; color: #241a14; }
    #rsvp-module .rsvp-feedback { margin-top: 1rem; color: #6b513f; }
    #rsvp-module .rsvp-center { text-align: center; }
    @media (max-width: 900px) {
        #rsvp-module .rsvp-gradient-top { display: none; }
        #rsvp-module .rsvp-gradient-mobile { display: block; }
        #rsvp-module .rsvp-section { padding: 4rem 1rem; }
        #rsvp-module .rsvp-logo-wrap { width: min(60vw, 280px); }
        #rsvp-module .rsvp-panel-grid,
        #rsvp-module .rsvp-form-row,
        #rsvp-module .rsvp-countdown-grid,
        #rsvp-module .rsvp-date-grid { grid-template-columns: 1fr; }
        #rsvp-module .rsvp-date-side,
        #rsvp-module .rsvp-date-day { text-align: center; }
    }
</style>

<div id="rsvp-module">
    <?php if ($isTrue($ambient['enabled'] ?? false) && trim((string) ($ambient['video'] ?? '')) !== '') { ?>
        <section class="p-f top left vh-100 vw-100 no-interaction rsvp-ambient" aria-hidden="true">
            <?=$renderVideo((string) $ambient['video'])?>
            <div class="rsvp-layer <?=$escape((string) ($ambient['overlay_class'] ?? ''))?>"></div>
        </section>
    <?php } ?>

    <?php if ($isTrue($intro['enabled'] ?? true)) { ?>
        <section id="intro" class="full-page vh-100 h-p-80 rsvp-section rsvp-intro rsvp-full-height">
            <?=$renderVideo((string) ($intro['video'] ?? ''))?>
            <div class="rsvp-gradient rsvp-gradient-top" style="<?= $escape((string) ($intro['desktop_overlay_style'] ?? 'background: linear-gradient(transparent 70%, #000000);')) ?>"></div>
            <div class="rsvp-gradient rsvp-gradient-mobile" style="<?= $escape((string) ($intro['mobile_overlay_style'] ?? 'background: linear-gradient(transparent 40%, #000000);')) ?>"></div>

            <div class="rsvp-content rsvp-intro-shell">
                <?php if ($introLogo !== '') { ?>
                    <div class="rsvp-logo-wrap">
                        <img src="<?=$escape($introLogo)?>" alt="<?=$escape($eventLabel !== '' ? $eventLabel : 'RSVP')?>" class="rsvp-logo">
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php if ($isTrue($messageSection['enabled'] ?? true) && $claimText !== '') { ?>
        <section class="rsvp-section">
            <div class="rsvp-content">
                <div class="rsvp-title"><?=$escape($claimText)?></div>
            </div>
        </section>
    <?php } ?>

    <?php if ($isTrue($dateSection['enabled'] ?? true) && $eventDate !== '') { ?>
        <section id="date-event" class="rsvp-section">
            <div class="rsvp-content rsvp-content-narrow">
                <?php if (trim((string) ($dateSection['eyebrow'] ?? '')) !== '') { ?>
                    <div class="rsvp-subtitle"><?= $escape((string) $dateSection['eyebrow']) ?></div>
                <?php } ?>

                <div class="rsvp-date-grid">
                    <div class="rsvp-date-side rsvp-center"><?= $escape(rsvpDatePart($eventDate, 'day')) ?></div>
                    <div class="rsvp-date-day"><?= $escape(rsvpDatePart($eventDate, 'day_number')) ?></div>
                    <div class="rsvp-date-side rsvp-center"><?= $escape(rsvpDatePart($eventDate, 'month')) ?></div>
                </div>

                <?php if (trim((string) ($dateSection['time_prefix'] ?? '')) !== '') { ?>
                    <div class="rsvp-date-time">
                        <span><?= $escape((string) $dateSection['time_prefix']) ?> <?= $escape(rsvpDatePart($eventDate, 'time')) ?></span>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php if (
        $isTrue($locationSection['enabled'] ?? true)
        && ($locationLogo !== '' || $locationAddress !== '' || $locationName !== '')
    ) { ?>
        <section class="rsvp-section">
            <div class="rsvp-content rsvp-content-narrow">
                <?php if (trim((string) ($locationSection['eyebrow'] ?? '')) !== '') { ?>
                    <div class="rsvp-subtitle"><?= $escape((string) $locationSection['eyebrow']) ?></div>
                <?php } ?>

                <?php if ($locationLogo !== '') { ?>
                    <div style="margin-top: 1.5rem;">
                        <?php if ($locationPositionUrl !== '') { ?>
                            <a href="<?=$escape($locationPositionUrl)?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?=$escape($locationLogo)?>" alt="<?=$escape($locationName !== '' ? $locationName : 'Location')?>" class="rsvp-location-logo">
                            </a>
                        <?php } else { ?>
                            <img src="<?=$escape($locationLogo)?>" alt="<?=$escape($locationName !== '' ? $locationName : 'Location')?>" class="rsvp-location-logo">
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($locationAddress !== '' || $locationName !== '') { ?>
                    <div class="rsvp-location-address">
                        <?php if ($locationAddressUrl !== '') { ?>
                            <a href="<?=$escape($locationAddressUrl)?>" target="_blank" rel="noopener noreferrer">
                                <?=$escape($locationAddress !== '' ? $locationAddress : $locationName)?>
                            </a>
                        <?php } else { ?>
                            <?=$escape($locationAddress !== '' ? $locationAddress : $locationName)?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php if ($isTrue($countdown['enabled'] ?? true) && $countdownDate !== '') { ?>
        <section id="countdown" class="rsvp-section">
            <div class="rsvp-content">
                <div class="rsvp-countdown-panel">
                    <?=$renderVideo((string) ($countdown['video'] ?? ($ambient['video'] ?? '')))?>
                    <div class="rsvp-layer"></div>
                    <div style="position: relative; z-index: 1;">
                        <div class="rsvp-title"><?= $escape((string) ($countdown['title'] ?? '')) ?></div>

                        <div class="rsvp-countdown-grid" data-target="<?=$escape($countdownDate)?>">
                            <?php foreach (['days', 'hours', 'minutes', 'seconds'] as $unit) { ?>
                                <div class="rsvp-center">
                                    <div class="rsvp-countdown-number" data-unit="<?=$escape($unit)?>">00</div>
                                    <div class="rsvp-countdown-line"></div>
                                    <div class="rsvp-countdown-label"><?= $escape((string) ($countdown['labels'][$unit] ?? strtoupper($unit))) ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php } ?>

    <section id="rsvp" class="rsvp-section">
        <div class="rsvp-content">
            <div class="rsvp-panel-grid">
                <div class="rsvp-panel">
                    <div class="rsvp-kicker"><?= $escape((string) ($loginContent['kicker'] ?? 'RSVP')) ?></div>
                    <h1 class="rsvp-panel-title"><?= $escape((string) ($state['home_title'] ?? 'RSVP')) ?></h1>
                    <?php if (!empty($state['home_text'])) { ?>
                        <div class="rsvp-panel-text"><?= nl2br($escape((string) $state['home_text'])) ?></div>
                    <?php } ?>

                    <div class="rsvp-meta">
                        <?php if ($eventLabel !== '') { ?>
                            <div class="rsvp-pill"><?= $escape($eventLabel) ?></div>
                        <?php } ?>
                        <?php if ($eventDate !== '') { ?>
                            <div class="rsvp-pill"><?= $escape($eventDate) ?></div>
                        <?php } ?>
                        <?php if ($hasSession) { ?>
                            <div class="rsvp-pill"><?= $escape((string) ($session['code'] ?? '')) ?></div>
                        <?php } ?>
                    </div>
                </div>

                <div class="rsvp-panel">
                    <?php if (!$canAccessForm) { ?>
                        <div class="rsvp-kicker"><?= $escape((string) ($loginContent['kicker'] ?? 'RSVP')) ?></div>
                        <h2 class="rsvp-panel-title"><?= $escape((string) ($loginContent['reserved_title'] ?? 'Accesso riservato')) ?></h2>
                        <div class="rsvp-panel-text"><?= nl2br($escape((string) ($loginContent['reserved_text'] ?? ''))) ?></div>
                        <div class="rsvp-actions" style="margin-top: 1.5rem;">
                            <a class="rsvp-btn" href="<?=$escape($loginUrl)?>"><?= $escape((string) ($formContent['invite_required_button'] ?? 'Vai al login RSVP')) ?></a>
                        </div>
                    <?php } else {
                            $participantsOptions = [];
                            for ($i = 1; $i <= $maxParticipants; $i++) {
                                $participantsOptions[(string) $i] = (string) $i;
                            }
                            $childrenOptions = [];
                            for ($i = 0; $i <= $maxChildren; $i++) {
                                $childrenOptions[(string) $i] = (string) $i;
                            }
                            $eventOptions = [];
                            foreach ($visibleEvents as $eventKey => $event) {
                                $eventOptions[(string) $eventKey] = (string) ($event['label'] ?? $eventKey);
                            }
                        ?>
                        <form id="rsvp-form" class="rsvp-form">
                            <input type="hidden" name="invite_code_id" value="<?=$escape((string) ($session['id'] ?? ''))?>">
                            <input type="hidden" name="locale" value="<?=$escape($locale)?>">

                            <?php if ($visibleEvents !== []) { ?>
                                <div class="rsvp-events-wrap">
                                    <?php if (count($visibleEvents) === 1) {
                                        $eventKey = array_key_first($visibleEvents);
                                        $event = $visibleEvents[$eventKey];
                                        ?>
                                        <input type="hidden" name="event_key" value="<?=$escape((string) $eventKey)?>">
                                        <label class="rsvp-label"><?= $escape((string) ($formContent['events_label'] ?? 'Eventi')) ?></label>
                                        <div class="rsvp-pill"><?= $escape((string) ($event['label'] ?? $eventKey)) ?></div>
                                    <?php } else { ?>
                                        <?= checkbox(
                                            (string) ($formContent['events_label'] ?? 'Eventi'),
                                            'events',
                                            $eventOptions,
                                            'checkbox'
                                        ) ?>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="rsvp-form-row">
                                <div><?= text(
                                    (string) ($formContent['contact_name_label'] ?? 'Nome referente'),
                                    'contact_name',
                                    null,
                                    'required'
                                ) ?></div>
                                <div><?= text(
                                    (string) ($formContent['contact_surname_label'] ?? 'Cognome referente'),
                                    'contact_surname',
                                    null,
                                    'required'
                                ) ?></div>
                            </div>

                            <div class="rsvp-form-row">
                                <div><?= phone(
                                    (string) ($formContent['contact_phone_label'] ?? 'Telefono'),
                                    'contact_phone',
                                    null,
                                    'required'
                                ) ?></div>
                                <div><?= email(
                                    (string) ($formContent['contact_email_label'] ?? 'Email'),
                                    'contact_email',
                                    null,
                                    'required'
                                ) ?></div>
                            </div>

                            <div class="rsvp-form-row">
                                <div><?= select(
                                    (string) ($formContent['participants_count_label'] ?? 'Numero adulti'),
                                    'participants_count',
                                    $participantsOptions,
                                    '1'
                                ) ?></div>
                                <?php if ($allowChildren) { ?>
                                    <div><?= select(
                                        (string) ($formContent['children_count_label'] ?? 'Numero bambini'),
                                        'children_count',
                                        $childrenOptions,
                                        '0'
                                    ) ?></div>
                                <?php } ?>
                            </div>

                            <div id="rsvp-participants"></div>

                            <template id="rsvp-participant-template" data-is-child="false">
                                <div class="rsvp-participant" data-participant-block data-is-child="false" data-index="__INDEX__">
                                    <div class="rsvp-participant-title">__TITLE__</div>
                                    <div class="rsvp-form-row">
                                        <div><?= text(
                                            (string) ($formContent['participant_name_label'] ?? 'Nome'),
                                            '',
                                            null,
                                            "data-field='name' data-index='__INDEX__' required"
                                        ) ?></div>
                                        <div><?= text(
                                            (string) ($formContent['participant_surname_label'] ?? 'Cognome'),
                                            '',
                                            null,
                                            "data-field='surname' data-index='__INDEX__' required"
                                        ) ?></div>
                                    </div>
                                    <div style="margin-top: 1rem;"><?= textarea(
                                        (string) ($formContent['participant_dietary_label'] ?? 'Esigenze alimentari'),
                                        '',
                                        null,
                                        "data-field='dietary_requirements' data-index='__INDEX__'"
                                    ) ?></div>
                                    <input type="hidden" data-field="is_child" data-index="__INDEX__" value="false">
                                </div>
                            </template>

                            <template id="rsvp-child-template" data-is-child="true">
                                <div class="rsvp-participant" data-participant-block data-is-child="true" data-index="__INDEX__">
                                    <div class="rsvp-participant-title">__TITLE__</div>
                                    <div class="rsvp-form-row">
                                        <div><?= text(
                                            (string) ($formContent['participant_name_label'] ?? 'Nome'),
                                            '',
                                            null,
                                            "data-field='name' data-index='__INDEX__' required"
                                        ) ?></div>
                                        <div><?= text(
                                            (string) ($formContent['participant_surname_label'] ?? 'Cognome'),
                                            '',
                                            null,
                                            "data-field='surname' data-index='__INDEX__' required"
                                        ) ?></div>
                                    </div>
                                    <div style="margin-top: 1rem;"><?= textarea(
                                        (string) ($formContent['participant_dietary_label'] ?? 'Esigenze alimentari'),
                                        '',
                                        null,
                                        "data-field='dietary_requirements' data-index='__INDEX__'"
                                    ) ?></div>
                                    <input type="hidden" data-field="is_child" data-index="__INDEX__" value="true">
                                </div>
                            </template>

                            <div><?= textarea(
                                (string) ($formContent['notes_label'] ?? 'Richieste aggiuntive'),
                                'notes'
                            ) ?></div>

                            <?php foreach ($customFields as $customKey => $customDef) { ?>
                                <div data-rsvp-custom-field="<?=$escape($customKey)?>">
                                    <?= $renderCustomField($customKey, $customDef) ?>
                                </div>
                            <?php } ?>

                            <?php if ($privacyField !== '') { ?>
                                <div><?=$privacyField?></div>
                            <?php } ?>

                            <?php if (!empty($state['require_image_release']) && $imageField !== '') { ?>
                                <div><?=$imageField?></div>
                            <?php } ?>

                            <div class="rsvp-actions">
                                <button class="rsvp-btn" type="submit"><?= $escape((string) ($formContent['submit_button'] ?? 'Invia conferma')) ?></button>
                                <a class="rsvp-btn rsvp-btn-secondary" href="<?=$escape($loginUrl)?>"><?= $escape((string) ($formContent['manage_invite_button'] ?? 'Gestisci codice invito')) ?></a>
                            </div>

                            <div id="rsvp-feedback" class="rsvp-feedback"></div>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    (() => {
        const countdown = document.querySelector('#rsvp-module .rsvp-countdown-grid[data-target]');

        if (countdown) {
            const target = new Date(countdown.dataset.target).getTime();
            const write = () => {
                const now = Date.now();
                const distance = target - now;
                const safeDistance = distance < 0 ? 0 : distance;
                const days = Math.floor(safeDistance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((safeDistance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((safeDistance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((safeDistance % (1000 * 60)) / 1000);
                const values = { days, hours, minutes, seconds };

                Object.entries(values).forEach(([unit, value]) => {
                    const node = countdown.querySelector(`[data-unit="${unit}"]`);

                    if (node) {
                        node.textContent = String(value).padStart(2, '0');
                    }
                });
            };

            write();
            window.setInterval(write, 1000);
        }

        const form = document.getElementById('rsvp-form');

        if (!form) {
            return;
        }

        const labels = <?=json_encode($ui, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
        const participantsContainer = document.getElementById('rsvp-participants');
        const adultsSelect = form.elements.participants_count;
        const childrenSelect = form.elements.children_count;
        const feedback = document.getElementById('rsvp-feedback');
        const adultTemplate = document.getElementById('rsvp-participant-template');
        const childTemplate = document.getElementById('rsvp-child-template');

        // I framework helpers (text/textarea/select) generano id randomici
        // condivisi nei <template>: quando cloni N blocchi, gli id si
        // duplicano e i <label for="..."> puntano al primo input. Rimappiamo
        // gli id per-blocco mantenendo la corrispondenza label↔input.
        function uniquifyIds(html, suffix) {
            const idMap = new Map();
            return html.replace(/(id|for)=(["'])(input_[a-z0-9]+|checkbox_[a-z0-9]+)\2/g,
                (_, attr, quote, id) => {
                    if (!idMap.has(id)) {
                        idMap.set(id, `${id}_${suffix}`);
                    }
                    return `${attr}=${quote}${idMap.get(id)}${quote}`;
                });
        }

        function participantHTML(index, title, isChild) {
            const template = isChild ? childTemplate : adultTemplate;
            if (!template) {
                return '';
            }
            const raw = template.innerHTML
                .split('__INDEX__').join(String(index))
                .replace('__TITLE__', title);
            return uniquifyIds(raw, `p${index}`);
        }

        function renderParticipants() {
            const adults = parseInt(adultsSelect ? adultsSelect.value : '1', 10) || 1;
            const children = parseInt(childrenSelect ? childrenSelect.value : '0', 10) || 0;
            let html = '';
            let cursor = 0;

            for (let i = 0; i < adults; i += 1) {
                html += participantHTML(cursor, `${labels.participantLabel} ${i + 1}`, false);
                cursor += 1;
            }

            for (let i = 0; i < children; i += 1) {
                html += participantHTML(cursor, `${labels.childLabel} ${i + 1}`, true);
                cursor += 1;
            }

            participantsContainer.innerHTML = html;

            // Reinizializza eventuali enhancement framework (es. wi-select)
            // appena iniettati. La funzione esiste se l'app la espone.
            if (typeof window.wiInputInit === 'function') {
                window.wiInputInit(participantsContainer);
            }
        }

        function collectParticipants() {
            const grouped = {};

            participantsContainer.querySelectorAll('[data-index][data-field]').forEach((element) => {
                const index = element.getAttribute('data-index');
                const field = element.getAttribute('data-field');

                if (!grouped[index]) {
                    grouped[index] = {};
                }

                grouped[index][field] = element.value;
            });

            return Object.values(grouped).map((participant) => ({
                name: participant.name || '',
                surname: participant.surname || '',
                dietary_requirements: participant.dietary_requirements || '',
                is_child: participant.is_child === 'true',
            }));
        }

        function collectCustomFields(payload) {
            const custom = {};

            form.querySelectorAll('[data-rsvp-custom-field]').forEach((wrapper) => {
                const key = wrapper.getAttribute('data-rsvp-custom-field');
                if (!key) return;

                // checkbox multipli: name finisce in []
                const multi = wrapper.querySelectorAll(`input[name="${key}[]"]:checked`);
                if (multi.length > 0) {
                    custom[key] = Array.from(multi).map((el) => el.value);
                    return;
                }

                const single = wrapper.querySelector(`[name="${key}"]`);
                if (!single) return;

                if (single.type === 'checkbox' || single.type === 'radio') {
                    custom[key] = single.checked ? (single.value || 'true') : '';
                    return;
                }

                custom[key] = (single.value || '').toString().trim();
            });

            payload.custom_fields = custom;
        }

        function collectConsentFields(payload) {
            form.querySelectorAll('input[name^="accept_"], input[name$="_id"], input[name="privacy"], input[name="photo_privacy"]').forEach((input) => {
                if (input.type === 'checkbox') {
                    if (input.checked) {
                        payload[input.name] = input.value || 'true';
                    }

                    return;
                }

                if (input.value !== '') {
                    payload[input.name] = input.value;
                }
            });
        }

        function selectedEvents() {
            if (form.elements.event_key) {
                return [form.elements.event_key.value];
            }

            return Array.from(form.querySelectorAll('input[name="events[]"]:checked')).map((element) => element.value);
        }

        renderParticipants();

        adultsSelect.addEventListener('change', renderParticipants);

        if (childrenSelect) {
            childrenSelect.addEventListener('change', renderParticipants);
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            feedback.textContent = labels.sendingText;

            const events = selectedEvents();

            if (form.querySelectorAll('input[name="events[]"]').length > 0 && events.length === 0) {
                feedback.textContent = labels.eventsRequiredText;
                return;
            }

            const payload = {
                invite_code_id: form.elements.invite_code_id.value || '',
                locale: form.elements.locale.value || '',
                contact_name: form.elements.contact_name.value.trim(),
                contact_surname: form.elements.contact_surname.value.trim(),
                contact_phone: form.elements.contact_phone.value.trim(),
                contact_email: form.elements.contact_email.value.trim(),
                notes: form.elements.notes.value.trim(),
                source_url: window.location.href,
                participants: collectParticipants(),
                events,
            };

            if (form.elements.event_key) {
                payload.event_key = form.elements.event_key.value;
            }

            collectConsentFields(payload);
            collectCustomFields(payload);

            const requestBody = new URLSearchParams();
            requestBody.set('post', 'true');
            requestBody.set('form', JSON.stringify(payload));

            try {
                const response = await fetch('<?= $escape($submitUrl) ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: requestBody.toString(),
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    throw new Error(data.response || data.message || labels.errorText);
                }

                form.reset();
                renderParticipants();
                feedback.textContent = data.response || labels.successText;
            } catch (error) {
                feedback.textContent = error.message || labels.errorText;
            }
        });
    })();
</script>
