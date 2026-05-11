<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\Plugin\Rsvp\Models\Authorization;

final class FrontendContext
{
    public static function state(?array $session = null): array
    {
        $settings = SubmissionNotifier::settings();
        $session = is_array($session) ? $session : InviteCodeSession::current();
        $authorization = self::authorization($session);
        $locale = function_exists('__l') ? __l() : 'it';
        $eventCatalog = self::eventCatalog($settings, $locale);
        $visibleEvents = self::visibleEvents($eventCatalog, $authorization);
        $limits = self::limits($settings, $authorization);
        $pageContent = self::pageContent($settings, $authorization, $locale);
        $featuredEvent = self::featuredEvent($pageContent, $visibleEvents, $settings, $locale);

        return [
            'locale' => $locale,
            'settings' => $settings,
            'session' => $session,
            'authorization' => $authorization,
            'page_content' => $pageContent,
            'events_catalog' => $eventCatalog,
            'featured_event' => $featuredEvent,
            'requires_invite_code' => self::isTrue($settings['require_invite_code'] ?? 'false'),
            'visible_events' => $visibleEvents,
            'allow_children' => $limits['allow_children'],
            'max_participants' => $limits['max_participants'],
            'max_children' => $limits['max_children'],
            'require_image_release' => self::isTrue($settings['require_image_release'] ?? 'false'),
            'login_title' => (string) ($pageContent['login']['title'] ?? self::legacyLoginTitle($settings)),
            'login_text' => (string) ($pageContent['login']['text'] ?? self::legacyLoginText($settings)),
            'home_title' => (string) ($pageContent['form']['headline'] ?? self::legacyHomeTitle($settings, $authorization)),
            'home_text' => (string) ($pageContent['form']['intro_text'] ?? self::legacyHomeText($settings, $authorization)),
        ];
    }

    public static function authorization(array $session): array
    {
        $authorizationId = (int) ($session['authorization_id'] ?? 0);

        if ($authorizationId <= 0) {
            return [];
        }

        $authorization = Authorization::find([
            'id' => $authorizationId,
            'deleted' => 'false',
        ], 1);

        return is_array($authorization) ? $authorization : [];
    }

    public static function eventCatalog(array $settings, string $locale): array
    {
        $catalog = rsvpDecodeJsonArray($settings['events_catalog_json'] ?? '[]');

        if ($catalog === []) {
            $catalog = self::legacyEventCatalog($settings);
        }

        $resolved = [];

        foreach ($catalog as $eventKey => $event) {
            if (!is_array($event)) {
                continue;
            }

            $eventKey = trim((string) $eventKey);

            if ($eventKey === '') {
                continue;
            }

            $resolved[$eventKey] = rsvpResolveLocalizedValue($event, $locale);
            $resolved[$eventKey]['key'] = $eventKey;
            $resolved[$eventKey]['label'] = trim((string) ($resolved[$eventKey]['label'] ?? $eventKey));
        }

        return $resolved;
    }

    public static function visibleEvents(array $eventCatalog, array $authorization): array
    {
        $allowedKeys = rsvpDecodeJsonArray($authorization['visible_event_keys_json'] ?? '[]');

        if ($allowedKeys === []) {
            return $eventCatalog;
        }

        $visible = [];

        foreach ($allowedKeys as $key) {
            $key = trim((string) $key);

            if ($key === '') {
                continue;
            }

            if (isset($eventCatalog[$key])) {
                $visible[$key] = $eventCatalog[$key];
            }
        }

        return $visible;
    }

    public static function limits(array $settings, array $authorization): array
    {
        $maxParticipants = max(
            1,
            (int) ($authorization['max_participants'] ?? ($settings['max_participants'] ?? 1))
        );

        $allowChildren = array_key_exists('allow_children', $authorization)
            ? self::isTrue($authorization['allow_children'] ?? 'false')
            : self::isTrue($settings['allow_children'] ?? 'false');

        $maxChildren = $allowChildren
            ? max(0, (int) ($authorization['max_children'] ?? ($settings['max_children'] ?? 0)))
            : 0;

        return [
            'max_participants' => $maxParticipants,
            'allow_children' => $allowChildren,
            'max_children' => $maxChildren,
        ];
    }

    public static function pageContent(array $settings, array $authorization, string $locale): array
    {
        $defaults = self::defaultPageContent();
        $settingsContent = rsvpDecodeJsonArray($settings['page_content_json'] ?? '[]');
        $authorizationContent = rsvpDecodeJsonArray($authorization['page_content_json'] ?? '[]');
        $merged = rsvpMergeRecursive($defaults, $settingsContent);
        $merged = rsvpMergeRecursive($merged, $authorizationContent);

        return rsvpResolveLocalizedValue($merged, $locale);
    }

    public static function featuredEvent(array $pageContent, array $visibleEvents, array $settings, string $locale): array
    {
        $featuredKey = trim((string) ($pageContent['date_section']['featured_event_key'] ?? ''));

        if ($featuredKey !== '' && isset($visibleEvents[$featuredKey])) {
            return $visibleEvents[$featuredKey];
        }

        if ($visibleEvents !== []) {
            return array_values($visibleEvents)[0];
        }

        $legacy = self::legacyEventCatalog($settings);

        if ($legacy !== []) {
            $resolved = self::eventCatalog($settings, $locale);
            return $resolved !== [] ? array_values($resolved)[0] : [];
        }

        return [];
    }

    private static function legacyEventCatalog(array $settings): array
    {
        $options = rsvpDecodeJsonArray($settings['event_options_json'] ?? '[]');

        if ($options !== []) {
            $catalog = [];

            foreach ($options as $key => $label) {
                if (!is_scalar($label) && !is_array($label)) {
                    continue;
                }

                $catalog[(string) $key] = [
                    'label' => $label,
                    'date' => (string) ($settings['event_starts_at'] ?? ''),
                    'location_name' => (string) ($settings['location_name'] ?? ''),
                    'location_address' => (string) ($settings['location_name'] ?? ''),
                    'location_address_url' => (string) ($settings['location_url'] ?? ''),
                    'location_position_url' => (string) ($settings['location_url'] ?? ''),
                ];
            }

            return $catalog;
        }

        if (
            trim((string) ($settings['event_name'] ?? '')) === ''
            && trim((string) ($settings['event_starts_at'] ?? '')) === ''
        ) {
            return [];
        }

        return [
            'main-event' => [
                'label' => (string) ($settings['event_name'] ?? 'Evento'),
                'date' => (string) ($settings['event_starts_at'] ?? ''),
                'location_name' => (string) ($settings['location_name'] ?? ''),
                'location_address' => (string) ($settings['location_name'] ?? ''),
                'location_address_url' => (string) ($settings['location_url'] ?? ''),
                'location_position_url' => (string) ($settings['location_url'] ?? ''),
            ],
        ];
    }

    private static function defaultPageContent(): array
    {
        return [
            'ambient_background' => [
                'enabled' => false,
                'video' => '',
                'overlay_class' => 'bg bg-black-50 blur-3',
            ],
            'intro' => [
                'enabled' => true,
                'video' => '',
                'desktop_overlay_style' => 'background: linear-gradient(transparent 70%, #000000);',
                'mobile_overlay_style' => 'background: linear-gradient(transparent 40%, #000000);',
                'logo_path' => '',
                'claim' => [
                    'it' => '',
                    'en' => '',
                ],
            ],
            'message_section' => [
                'enabled' => true,
                'content' => [
                    'it' => '',
                    'en' => '',
                ],
            ],
            'date_section' => [
                'enabled' => true,
                'featured_event_key' => '',
                'eyebrow' => [
                    'it' => 'Ti aspettiamo',
                    'en' => 'We are waiting for you',
                ],
                'time_prefix' => [
                    'it' => 'dalle ore',
                    'en' => 'from',
                ],
            ],
            'location_section' => [
                'enabled' => true,
                'eyebrow' => [
                    'it' => 'Location',
                    'en' => 'Location',
                ],
            ],
            'countdown' => [
                'enabled' => true,
                'video' => '',
                'title' => [
                    'it' => 'Festeggeremo tra...',
                    'en' => 'We will celebrate in...',
                ],
                'labels' => [
                    'days' => [
                        'it' => 'Giorni',
                        'en' => 'Days',
                    ],
                    'hours' => [
                        'it' => 'Ore',
                        'en' => 'Hours',
                    ],
                    'minutes' => [
                        'it' => 'Minuti',
                        'en' => 'Minutes',
                    ],
                    'seconds' => [
                        'it' => 'Secondi',
                        'en' => 'Seconds',
                    ],
                ],
            ],
            'login' => [
                'kicker' => 'RSVP',
                'title' => [
                    'it' => 'Accesso RSVP',
                    'en' => 'RSVP Access',
                ],
                'text' => [
                    'it' => 'Inserisci il tuo codice invito per accedere alla pagina RSVP.',
                    'en' => 'Enter your invite code to access the RSVP page.',
                ],
                'code_placeholder' => [
                    'it' => 'Inserisci il codice invito',
                    'en' => 'Enter your invite code',
                ],
                'reserved_title' => [
                    'it' => 'Accesso riservato',
                    'en' => 'Reserved access',
                ],
                'reserved_text' => [
                    'it' => 'Per compilare questo RSVP devi prima autenticarti con un codice invito valido.',
                    'en' => 'You must authenticate with a valid invite code before filling in this RSVP.',
                ],
                'login_button' => [
                    'it' => 'Accedi',
                    'en' => 'Sign in',
                ],
                'home_button' => [
                    'it' => 'Vai al form RSVP',
                    'en' => 'Open RSVP form',
                ],
                'session_text' => [
                    'it' => 'Sessione attiva con codice',
                    'en' => 'Active session with code',
                ],
                'logout_button' => [
                    'it' => 'Esci',
                    'en' => 'Logout',
                ],
                'loading_text' => [
                    'it' => 'Accesso in corso...',
                    'en' => 'Signing in...',
                ],
                'error_text' => [
                    'it' => 'Accesso non riuscito.',
                    'en' => 'Login failed.',
                ],
                'free_text' => [
                    'it' => 'Questo RSVP è libero: puoi entrare direttamente nella pagina di conferma.',
                    'en' => 'This RSVP is open: you can go directly to the confirmation page.',
                ],
            ],
            'form' => [
                'headline' => [
                    'it' => 'Conferma la tua partecipazione',
                    'en' => 'Confirm your attendance',
                ],
                'intro_text' => [
                    'it' => '',
                    'en' => '',
                ],
                'events_label' => [
                    'it' => 'Eventi',
                    'en' => 'Events',
                ],
                'events_required_text' => [
                    'it' => 'Seleziona almeno un evento.',
                    'en' => 'Select at least one event.',
                ],
                'contact_name_label' => [
                    'it' => 'Nome referente',
                    'en' => 'Contact first name',
                ],
                'contact_surname_label' => [
                    'it' => 'Cognome referente',
                    'en' => 'Contact last name',
                ],
                'contact_phone_label' => [
                    'it' => 'Telefono',
                    'en' => 'Phone',
                ],
                'contact_email_label' => [
                    'it' => 'Email',
                    'en' => 'Email',
                ],
                'participants_count_label' => [
                    'it' => 'Numero adulti',
                    'en' => 'Number of adults',
                ],
                'children_count_label' => [
                    'it' => 'Numero bambini',
                    'en' => 'Number of children',
                ],
                'participant_label' => [
                    'it' => 'Partecipante',
                    'en' => 'Participant',
                ],
                'child_label' => [
                    'it' => 'Bambino',
                    'en' => 'Child',
                ],
                'participant_name_label' => [
                    'it' => 'Nome',
                    'en' => 'First name',
                ],
                'participant_surname_label' => [
                    'it' => 'Cognome',
                    'en' => 'Last name',
                ],
                'participant_dietary_label' => [
                    'it' => 'Esigenze alimentari',
                    'en' => 'Dietary requirements',
                ],
                'notes_label' => [
                    'it' => 'Richieste aggiuntive',
                    'en' => 'Additional notes',
                ],
                'privacy_fallback_text' => [
                    'it' => 'Acconsento al trattamento dei dati personali.',
                    'en' => 'I consent to the processing of personal data.',
                ],
                'image_release_fallback_text' => [
                    'it' => 'Acconsento alla raccolta e all’utilizzo di immagini fotografiche e video.',
                    'en' => 'I consent to the collection and use of photos and videos.',
                ],
                'submit_button' => [
                    'it' => 'Invia conferma',
                    'en' => 'Send RSVP',
                ],
                'sending_text' => [
                    'it' => 'Invio in corso...',
                    'en' => 'Sending RSVP...',
                ],
                'success_text' => [
                    'it' => 'Conferma inviata con successo.',
                    'en' => 'RSVP sent successfully.',
                ],
                'error_text' => [
                    'it' => 'Invio non riuscito.',
                    'en' => 'Unable to send RSVP.',
                ],
                'manage_invite_button' => [
                    'it' => 'Gestisci codice invito',
                    'en' => 'Manage invite code',
                ],
                'invite_required_button' => [
                    'it' => 'Vai al login RSVP',
                    'en' => 'Go to RSVP login',
                ],
            ],
        ];
    }

    private static function legacyLoginTitle(array $settings): string
    {
        return trim((string) ($settings['login_title'] ?? '')) !== ''
            ? (string) $settings['login_title']
            : 'Accesso RSVP';
    }

    private static function legacyLoginText(array $settings): string
    {
        return trim((string) ($settings['login_text'] ?? '')) !== ''
            ? (string) $settings['login_text']
            : 'Inserisci il tuo codice invito per accedere alla pagina RSVP.';
    }

    private static function legacyHomeTitle(array $settings, array $authorization): string
    {
        return trim((string) ($authorization['home_title'] ?? '')) !== ''
            ? (string) $authorization['home_title']
            : (trim((string) ($settings['home_title'] ?? '')) !== '' ? (string) $settings['home_title'] : 'Conferma la tua partecipazione');
    }

    private static function legacyHomeText(array $settings, array $authorization): string
    {
        return trim((string) ($authorization['home_text'] ?? '')) !== ''
            ? (string) $authorization['home_text']
            : (string) ($settings['home_text'] ?? '');
    }

    private static function isTrue(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
