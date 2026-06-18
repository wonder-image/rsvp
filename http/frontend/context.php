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
$authorization = [];

if ($authorizationId > 0) {
    $authorization = Authorization::find([
        'id' => $authorizationId,
        'deleted' => 'false',
    ], 1);

    if (!is_array($authorization)) {
        $authorization = [];
    }
}

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
        'location_name' => (string) ($event['location_name'] ?? ''),
        'location_address' => (string) ($event['location_address'] ?? ''),
        'location_address_url' => (string) ($event['location_address_url'] ?? ''),
        'location_position_url' => (string) ($event['location_position_url'] ?? ''),
        'location_logo' => (string) ($event['location_logo'] ?? ''),
        'position' => (int) ($event['position'] ?? 0),
    ], $locale);
    
    $eventCatalog[$eventKey]['key'] = $eventKey;
    $eventCatalog[$eventKey]['label'] = trim((string) ($eventCatalog[$eventKey]['label'] ?? $eventKey));
}

uasort($eventCatalog, static function (array $left, array $right): int {
    return ((int) ($left['position'] ?? 0) <=> (int) ($right['position'] ?? 0))
        ?: strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
});

$allowedKeys = rsvpDecodeJsonArray($authorization['visible_event_keys_json'] ?? '[]');
$visibleEvents = [];

if ($allowedKeys === []) {
    $visibleEvents = $eventCatalog;
} else {
    foreach ($allowedKeys as $key) {
        $key = trim((string) $key);

        if ($key !== '' && isset($eventCatalog[$key])) {
            $visibleEvents[$key] = $eventCatalog[$key];
        }
    }
}

$maxParticipants = max(
    1,
    (int) ($authorization['max_participants'] ?? ($settings['max_participants'] ?? 1))
);

$allowChildren = array_key_exists('allow_children', $authorization)
    ? $isTrue($authorization['allow_children'] ?? 'false')
    : $isTrue($settings['allow_children'] ?? 'false');

$maxChildren = $allowChildren
    ? max(0, (int) ($authorization['max_children'] ?? ($settings['max_children'] ?? 0)))
    : 0;
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
    'requires_invite_code' => $isTrue($settings['require_invite_code'] ?? 'false'),
    'visible_events' => $visibleEvents,
    'allow_children' => $allowChildren,
    'max_participants' => $maxParticipants,
    'max_children' => $maxChildren,
    'enable_attendance_status' => rsvpAttendanceStatusEnabled($settings),
    'require_image_release' => $isTrue($settings['require_image_release'] ?? 'false'),
    'custom_fields' => ExtensionRegistry::inputs(),
];
