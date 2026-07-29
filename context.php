<?php

use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\Event;
use Wonder\Plugin\Rsvp\Services\InviteCodeSession;
use Wonder\Plugin\Rsvp\Services\SubmissionNotifier;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
use Wonder\Support\Prettify\Date as PrettyDate;

$isTrue = static fn (mixed $value): bool => in_array(
    strtolower(trim((string) $value)),
    ['1', 'true', 'yes', 'on'],
    true
);

$session = is_array($session ?? null) ? $session : InviteCodeSession::current();
$settings = SubmissionNotifier::settings();
$authorizationId = (int) ($session['authorization_id'] ?? 0);

// Autorizzazione da codice invito (se presente in sessione).
$codeAuthorization = [];

if ($authorizationId > 0) {
    $found = Authorization::find([
        'id' => $authorizationId,
        'deleted' => 'false',
    ], 1);

    $codeAuthorization = is_array($found) ? $found : [];
}

// Autorizzazione "Libero" (accesso pubblico senza password): al più una.
$freeAuthorization = [];
$foundFree = Authorization::find([
    'access' => 'free',
    'deleted' => 'false',
], 1);

if (is_array($foundFree) && $foundFree !== []) {
    $freeAuthorization = $foundFree;
}

// Autorizzazione attiva: quella del codice se c'è, altrimenti quella Libero.
// Serve SEMPRE un'autorizzazione: senza nessuna delle due il form è chiuso.
$authorization = $codeAuthorization !== [] ? $codeAuthorization : $freeAuthorization;

// Serve un codice solo quando non esiste un accesso pubblico (nessuna Libero).
$requiresInviteCode = $freeAuthorization === [];

$locale = __l();

try {
    $catalog = Event::all();
} catch (\Throwable) {
    $catalog = [];
}

$eventCatalog = [];

foreach ($catalog as $eventKey => $event) {
    if (!is_array($event)) {
        continue;
    }

    $eventKey = trim((string) ($event['code'] ?? $eventKey));

    if ($eventKey === '' || !$isTrue($event['active'] ?? 'true')) {
        continue;
    }

    $date = $event['starts_at'];
    $endDate = trim((string) ($event['ends_at'] ?? ''));
    $hasEnd = $endDate !== '' && strtotime($endDate) !== false;

    $eventCatalog[$eventKey] = rsvpResolveLocalizedValue([
        'key' => $eventKey,
        'label' => (string) ($event['name'] ?? $eventKey),
        'description' => (string) ($event['description'] ?? ''),
        'date' => $date,
        'pretty_date' => prettyDate($date, false),
        'hour' => date('H:i', strtotime($date)),
        'day' => date('d', strtotime($date)),
        'pretty_day' => PrettyDate::day($date),
        'month' => date('m', strtotime($date)),
        'pretty_month' => PrettyDate::month($date),
        'end_date' => $hasEnd ? $endDate : '',
        'pretty_end_date' => $hasEnd ? prettyDate($endDate, false) : '',
        'end_hour' => $hasEnd ? date('H:i', strtotime($endDate)) : '',
        'end_day' => $hasEnd ? date('d', strtotime($endDate)) : '',
        'pretty_end_day' => $hasEnd ? PrettyDate::day($endDate) : '',
        'end_month' => $hasEnd ? date('m', strtotime($endDate)) : '',
        'pretty_end_month' => $hasEnd ? PrettyDate::month($endDate) : '',
        'location_name' => (string) ($event['location_name'] ?? ''),
        'location_address' => (string) ($event['location_address'] ?? ''),
        'location_address_url' => (string) ($event['location_address_url'] ?? ''),
        'location_position_url' => (string) ($event['location_position_url'] ?? ''),
        'location_logo' => (string) ($event['location_logo'] ?? ''),
        'location_logo_url' => (string) ($event['locationLogoUrl'] ?? ''),
        'position' => (int) ($event['position'] ?? 0),
    ], $locale);
    
    $eventCatalog[$eventKey]['id'] = (int) ($event['id'] ?? 0);
    $eventCatalog[$eventKey]['key'] = $eventKey;
    $eventCatalog[$eventKey]['label'] = trim((string) ($eventCatalog[$eventKey]['label'] ?? $eventKey));
}

uasort($eventCatalog, static function (array $left, array $right): int {
    return ((int) ($left['position'] ?? 0) <=> (int) ($right['position'] ?? 0))
        ?: strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
});

$allowedIds = array_values(array_filter(
    array_map('intval', rsvpDecodeJsonArray($authorization['visible_event_ids'] ?? '[]')),
    static fn (int $id): bool => $id > 0
));
$visibleEvents = [];

if ($allowedIds === []) {
    $visibleEvents = $eventCatalog;
} else {
    foreach ($eventCatalog as $eventKey => $event) {
        if (in_array((int) ($event['id'] ?? 0), $allowedIds, true)) {
            $visibleEvents[$eventKey] = $event;
        }
    }
}

// Configurazione del form risolta SEMPRE dall'autorizzazione attiva (i campi
// non stanno più in Impostazioni). Senza autorizzazione valgono i default
// del modulo: un RSVP prevede sempre un'autorizzazione.
$maxParticipants = max(1, (int) ($authorization['max_participants'] ?? 1));
$allowChildren = $isTrue($authorization['allow_children'] ?? 'false');
$maxChildren = $allowChildren
    ? max(0, (int) ($authorization['max_children'] ?? 0))
    : 0;
$enableAttendance = rsvpAttendanceStatusEnabled($authorization);
$requireImageRelease = $isTrue($authorization['require_image_release'] ?? 'false');
$fieldModes = rsvpFieldModes(is_array($authorization) ? $authorization : []);

$featuredEvent = [];

if ($visibleEvents !== []) {
    $featuredEvent = array_values($visibleEvents)[0];
}

return [
    'locale' => $locale,
    'settings' => $settings,
    'session' => $session,
    'authorization' => $authorization,
    'events_catalog' => $eventCatalog,
    'featured_event' => $featuredEvent,
    'requires_invite_code' => $requiresInviteCode,
    'visible_events' => $visibleEvents,
    'allow_children' => $allowChildren,
    'max_participants' => $maxParticipants,
    'max_children' => $maxChildren,
    'enable_attendance_status' => $enableAttendance,
    'require_image_release' => $requireImageRelease,
    'field_modes' => $fieldModes,
    'custom_fields' => ExtensionRegistry::inputs(),
];
