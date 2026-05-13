<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\Event;

final class FrontendContext
{
    public static function state(?array $session = null): array
    {
        $settings = SubmissionNotifier::settings();
        $session = is_array($session) ? $session : InviteCodeSession::current();
        $authorization = self::authorization($session);
        $locale = function_exists('__l') ? __l() : 'it';
        $eventCatalog = self::eventCatalog($locale);
        $visibleEvents = self::visibleEvents($eventCatalog, $authorization);
        $limits = self::limits($settings, $authorization);
        $pageContent = self::pageContent($locale);
        $featuredEvent = self::featuredEvent($pageContent, $visibleEvents);

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
            'enable_attendance_status' => rsvpAttendanceStatusEnabled($settings),
            'require_image_release' => self::isTrue($settings['require_image_release'] ?? 'false'),
            'custom_fields' => ExtensionRegistry::fields(),
            'login_title' => trim((string) ($settings['login_title'] ?? '')) !== ''
                ? (string) $settings['login_title']
                : rsvp_trans('rsvp.frontend.login.title', 'Accesso RSVP'),
            'login_text' => trim((string) ($settings['login_text'] ?? '')) !== ''
                ? (string) $settings['login_text']
                : rsvp_trans('rsvp.frontend.login.text', 'Inserisci il tuo codice invito per accedere alla pagina RSVP.'),
            'home_title' => rsvp_trans('rsvp.frontend.home.section_title', 'Conferma la tua partecipazione'),
            'home_text' => rsvp_trans('rsvp.frontend.home.intro_text', ''),
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

    public static function eventCatalog(string $locale): array
    {
        try {
            $catalog = Event::all();
        } catch (\Throwable) {
            // Tabella rsvp_event ancora non creata sul consumer (primo avvio):
            // restituiamo un catalogo vuoto, la view userà i fallback.
            $catalog = [];
        }
        $resolved = [];

        foreach ($catalog as $eventKey => $event) {
            if (!is_array($event)) {
                continue;
            }

            $eventKey = trim((string) ($event['code'] ?? $eventKey));

            if ($eventKey === '') {
                continue;
            }

            if (!self::isTrue($event['active'] ?? 'true')) {
                continue;
            }

            $resolved[$eventKey] = rsvpResolveLocalizedValue([
                'key' => $eventKey,
                'label' => (string) ($event['name'] ?? $eventKey),
                'description' => (string) ($event['description'] ?? ''),
                'date' => (string) ($event['starts_at'] ?? ''),
                'location_name' => (string) ($event['location_name'] ?? ''),
                'location_address' => (string) ($event['location_address'] ?? ''),
                'location_address_url' => (string) ($event['location_address_url'] ?? ''),
                'location_position_url' => (string) ($event['location_position_url'] ?? ''),
                'location_logo' => (string) ($event['location_logo'] ?? ''),
                'position' => (int) ($event['position'] ?? 0),
            ], $locale);
            $resolved[$eventKey]['key'] = $eventKey;
            $resolved[$eventKey]['label'] = trim((string) ($resolved[$eventKey]['label'] ?? $eventKey));
        }

        uasort($resolved, static function (array $left, array $right): int {
            return ((int) ($left['position'] ?? 0) <=> (int) ($right['position'] ?? 0))
                ?: strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

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

    public static function pageContent(string $locale): array
    {
        return rsvpResolveLocalizedValue(self::defaultPageContent(), $locale);
    }

    public static function featuredEvent(array $pageContent, array $visibleEvents): array
    {
        $featuredKey = trim((string) ($pageContent['date_section']['featured_event_key'] ?? ''));

        if ($featuredKey !== '' && isset($visibleEvents[$featuredKey])) {
            return $visibleEvents[$featuredKey];
        }

        if ($visibleEvents !== []) {
            return array_values($visibleEvents)[0];
        }

        return [];
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
                'enabled' => false,
                'video' => '',
                'desktop_overlay_style' => 'background: linear-gradient(transparent 70%, #000000);',
                'mobile_overlay_style' => 'background: linear-gradient(transparent 40%, #000000);',
                'logo_path' => '',
            ],
            'message_section' => [
                'enabled' => false,
                'content' => rsvp_trans('rsvp.frontend.home.intro_text', ''),
            ],
            'date_section' => [
                'enabled' => true,
                'featured_event_key' => '',
                'eyebrow' => rsvp_trans('rsvp.frontend.event_date.eyebrow', 'Ti aspettiamo'),
                'time_prefix' => rsvp_trans('rsvp.frontend.event_date.time_prefix', 'dalle ore'),
            ],
            'location_section' => [
                'enabled' => true,
                'eyebrow' => rsvp_trans('rsvp.frontend.location.eyebrow', 'Location'),
            ],
            'countdown' => [
                'enabled' => true,
                'video' => '',
                'title' => rsvp_trans('rsvp.frontend.countdown.title', 'Festeggeremo tra...'),
                'labels' => [
                    'days' => rsvp_trans('rsvp.frontend.countdown.days', 'Giorni'),
                    'hours' => rsvp_trans('rsvp.frontend.countdown.hours', 'Ore'),
                    'minutes' => rsvp_trans('rsvp.frontend.countdown.minutes', 'Minuti'),
                    'seconds' => rsvp_trans('rsvp.frontend.countdown.seconds', 'Secondi'),
                ],
            ],
            'login' => [
                'kicker' => rsvp_trans('rsvp.frontend.login.kicker', 'RSVP'),
                'title' => rsvp_trans('rsvp.frontend.login.title', 'Accesso RSVP'),
                'text' => rsvp_trans('rsvp.frontend.login.text', 'Inserisci il tuo codice invito per accedere alla pagina RSVP.'),
                'code_placeholder' => rsvp_trans('rsvp.frontend.login.code_placeholder', 'Inserisci il codice invito'),
                'reserved_title' => rsvp_trans('rsvp.frontend.login.reserved_title', 'Accesso riservato'),
                'reserved_text' => rsvp_trans('rsvp.frontend.login.reserved_text', 'Per compilare questo RSVP devi prima autenticarti con un codice invito valido.'),
                'login_button' => rsvp_trans('rsvp.frontend.login.login_button', 'Accedi'),
                'home_button' => rsvp_trans('rsvp.frontend.login.home_button', 'Vai al form RSVP'),
                'session_text' => rsvp_trans('rsvp.frontend.login.session_text', 'Sessione attiva con codice'),
                'logout_button' => rsvp_trans('rsvp.frontend.login.logout_button', 'Esci'),
                'loading_text' => rsvp_trans('rsvp.frontend.login.loading_text', 'Accesso in corso...'),
                'error_text' => rsvp_trans('rsvp.frontend.login.error_text', 'Accesso non riuscito.'),
                'free_text' => rsvp_trans('rsvp.frontend.login.free_text', 'Questo RSVP è libero: puoi entrare direttamente nella pagina di conferma.'),
            ],
            'form' => [
                'headline' => rsvp_trans('rsvp.frontend.home.section_title', 'Conferma la tua partecipazione'),
                'intro_text' => rsvp_trans('rsvp.frontend.home.intro_text', ''),
                'events_label' => rsvp_trans('rsvp.frontend.form.events_label', 'Eventi'),
                'events_required_text' => rsvp_trans('rsvp.frontend.form.events_required_text', 'Seleziona almeno un evento.'),
                'contact_name_label' => rsvp_trans('rsvp.frontend.form.contact_name_label', 'Nome referente'),
                'contact_surname_label' => rsvp_trans('rsvp.frontend.form.contact_surname_label', 'Cognome referente'),
                'contact_phone_label' => rsvp_trans('rsvp.frontend.form.contact_phone_label', 'Telefono'),
                'contact_email_label' => rsvp_trans('rsvp.frontend.form.contact_email_label', 'Email'),
                'participants_count_label' => rsvp_trans('rsvp.frontend.form.participants_count_label', 'Numero adulti'),
                'children_count_label' => rsvp_trans('rsvp.frontend.form.children_count_label', 'Numero bambini'),
                'participant_label' => rsvp_trans('rsvp.frontend.form.participant_label', 'Partecipante'),
                'child_label' => rsvp_trans('rsvp.frontend.form.child_label', 'Bambino'),
                'participant_name_label' => rsvp_trans('rsvp.frontend.form.participant_name_label', 'Nome'),
                'participant_surname_label' => rsvp_trans('rsvp.frontend.form.participant_surname_label', 'Cognome'),
                'participant_dietary_label' => rsvp_trans('rsvp.frontend.form.participant_dietary_label', 'Esigenze alimentari'),
                'notes_label' => rsvp_trans('rsvp.frontend.form.notes_label', 'Richieste aggiuntive'),
                'privacy_fallback_text' => rsvp_trans('rsvp.frontend.form.privacy_fallback_text', 'Acconsento al trattamento dei dati personali.'),
                'image_release_fallback_text' => rsvp_trans('rsvp.frontend.form.image_release_fallback_text', 'Acconsento alla raccolta e all’utilizzo di immagini fotografiche e video.'),
                'submit_button' => rsvp_trans('rsvp.frontend.form.submit_label', 'Invia risposta'),
                'sending_text' => rsvp_trans('rsvp.frontend.form.sending_text', 'Invio in corso...'),
                'success_text' => rsvp_trans('rsvp.frontend.form.success_text', 'La tua risposta è stata registrata!'),
                'error_text' => rsvp_trans('rsvp.frontend.form.error_text', 'Non è stato possibile inviare la tua risposta. Riprova.'),
                'manage_invite_button' => rsvp_trans('rsvp.frontend.form.manage_invite_button', 'Gestisci codice invito'),
                'invite_required_button' => rsvp_trans('rsvp.frontend.form.invite_required_button', 'Vai al login RSVP'),
            ],
        ];
    }

    private static function isTrue(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
