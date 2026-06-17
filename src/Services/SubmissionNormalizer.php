<?php

namespace Wonder\Plugin\Rsvp\Services;

use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\InviteCode;
use Wonder\Plugin\Rsvp\Models\InviteGroup;
use Wonder\Plugin\Rsvp\Models\Response;

final class SubmissionNormalizer
{
    public static function fromPayload(array $payload): array
    {
        $payload = self::normalizePayload($payload);
        $attendanceStatus = self::attendanceStatus($payload, SubmissionNotifier::settings());
        $participants = $attendanceStatus === 'declined'
            ? []
            : self::participants($payload);
        $session = InviteCodeSession::current();
        $inviteCodeId = (int) ($payload['invite_code_id'] ?? $payload['password_id'] ?? $session['id'] ?? 0);
        $inviteGroupCode = self::inviteGroupCode($inviteCodeId);
        $authorizationCode = self::authorizationCode($inviteCodeId);
        $events = self::events($payload);
        $eventKey = $payload['event_key'] ?? ($events[0] ?? null);
        $legalDocuments = self::legalDocuments($payload);
        $privacyAccepted = self::boolean(
            $legalDocuments['privacy_policy']['accepted'] ?? ($payload['privacy'] ?? false)
        );
        $photoAccepted = self::boolean(
            $legalDocuments['image_release']['accepted'] ?? ($payload['photo'] ?? ($payload['photo_privacy'] ?? false))
        );
        $customFieldColumns = self::customFieldColumns($payload);

        return array_merge([
            'attendance_status' => $attendanceStatus,
            'invite_code_id' => $inviteCodeId > 0 ? $inviteCodeId : null,
            'invite_code' => $session['code'] ?? null,
            'invite_group_code' => $inviteGroupCode !== '' ? $inviteGroupCode : null,
            'authorization_code' => $authorizationCode !== '' ? $authorizationCode : null,
            'event_key' => is_string($eventKey) && trim($eventKey) !== '' ? trim($eventKey) : null,
            'locale' => self::string($payload, ['locale', 'lang']),
            'contact_name' => self::string($payload, ['contact_name']) ?: ($participants[0]['name'] ?? ''),
            'contact_surname' => self::string($payload, ['contact_surname']) ?: ($participants[0]['surname'] ?? ''),
            'contact_phone' => self::string($payload, ['contact_phone', 'phone', 'cel']),
            'contact_email' => strtolower(self::string($payload, ['contact_email', 'email'])),
            'participants_json' => json_encode($participants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'participants_count' => count($participants),
            'children_count' => count(array_filter(
                $participants,
                static fn ($participant) => !empty($participant['is_child'])
            )),
            'notes' => self::string($payload, ['notes', 'requests', 'request']),
            'events_json' => $events !== [] ? json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'consents_json' => json_encode([
                'privacy' => $privacyAccepted,
                'photo' => $photoAccepted,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'legal_documents_json' => $legalDocuments !== []
                ? json_encode($legalDocuments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'metadata_json' => json_encode(self::metadata($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'source_url' => self::string($payload, ['source_url', 'request_url']),
        ], $customFieldColumns);
    }

    public static function participantsFromNormalized(array $normalized): array
    {
        return rsvpDecodeJsonArray($normalized['participants_json'] ?? '[]');
    }

    public static function eventsFromNormalized(array $normalized): array
    {
        return rsvpDecodeJsonArray($normalized['events_json'] ?? '[]');
    }

    public static function consents(array $normalized): array
    {
        return rsvpDecodeJsonArray($normalized['consents_json'] ?? '[]');
    }

    public static function legalDocumentsFromNormalized(array $normalized): array
    {
        return rsvpDecodeJsonArray($normalized['legal_documents_json'] ?? '[]');
    }

    private static function normalizePayload(array $payload): array
    {
        if (isset($payload['form'])) {
            $form = $payload['form'];

            if (is_string($form)) {
                $decoded = json_decode($form, true);
                $form = is_array($decoded) ? $decoded : [];
            }

            if (is_array($form)) {
                unset($payload['form'], $payload['post']);
                $payload = array_replace($form, $payload);
            }
        }

        foreach ($payload as $key => $value) {
            if (is_string($value) && self::isJsonArray($value)) {
                $payload[$key] = json_decode($value, true);
            }
        }

        return $payload;
    }

    private static function participants(array $payload): array
    {
        if (isset($payload['participants']) && is_array($payload['participants'])) {
            $participants = [];

            foreach ($payload['participants'] as $participant) {
                $normalized = self::normalizeParticipant($participant);

                if ($normalized !== null) {
                    $participants[] = $normalized;
                }
            }

            if ($participants !== []) {
                return $participants;
            }
        }

        $adults = self::legacyParticipants(
            $payload['name'] ?? [],
            $payload['surname'] ?? [],
            $payload['dietary_requirements'] ?? ($payload['allergies'] ?? []),
            false
        );

        $children = self::legacyParticipants(
            $payload['children_name'] ?? [],
            $payload['children_surname'] ?? [],
            $payload['children_dietary_requirements'] ?? [],
            true
        );

        $partnerName = trim((string) ($payload['partner_name'] ?? ''));
        $partnerSurname = trim((string) ($payload['partner_surname'] ?? ''));

        if ($partnerName !== '' || $partnerSurname !== '') {
            $adults[] = [
                'name' => $partnerName,
                'surname' => $partnerSurname,
                'dietary_requirements' => '',
                'is_child' => false,
            ];
        }

        $participants = array_merge($adults, $children);

        if ($participants !== []) {
            return $participants;
        }

        $contactName = self::string($payload, ['contact_name', 'name']);
        $contactSurname = self::string($payload, ['contact_surname', 'surname']);

        return [[
            'name' => $contactName,
            'surname' => $contactSurname,
            'dietary_requirements' => self::string($payload, ['allergies']),
            'is_child' => false,
        ]];
    }

    private static function legacyParticipants(mixed $names, mixed $surnames, mixed $dietary, bool $isChild): array
    {
        $names = is_array($names) ? $names : [$names];
        $surnames = is_array($surnames) ? $surnames : [$surnames];
        $dietary = is_array($dietary) ? $dietary : [$dietary];
        $participants = [];
        $total = max(count($names), count($surnames), count($dietary));

        for ($index = 0; $index < $total; $index++) {
            $participant = self::normalizeParticipant([
                'name' => $names[$index] ?? '',
                'surname' => $surnames[$index] ?? '',
                'dietary_requirements' => $dietary[$index] ?? '',
                'is_child' => $isChild,
            ]);

            if ($participant !== null) {
                $participants[] = $participant;
            }
        }

        return $participants;
    }

    private static function normalizeParticipant(mixed $participant): ?array
    {
        if (!is_array($participant)) {
            return null;
        }

        $name = trim((string) ($participant['name'] ?? ''));
        $surname = trim((string) ($participant['surname'] ?? ''));
        $dietary = trim((string) ($participant['dietary_requirements'] ?? ($participant['allergies'] ?? '')));
        $isChild = self::boolean($participant['is_child'] ?? (($participant['type'] ?? '') === 'child'));

        if ($name === '' && $surname === '' && $dietary === '') {
            return null;
        }

        return [
            'name' => $name,
            'surname' => $surname,
            'dietary_requirements' => $dietary,
            'is_child' => $isChild,
        ];
    }

    private static function events(array $payload): array
    {
        $value = $payload['events'] ?? ($payload['event'] ?? []);

        if (!is_array($value)) {
            $value = [$value];
        }

        $events = [];

        foreach ($value as $event) {
            if (!is_scalar($event)) {
                continue;
            }

            $event = trim((string) $event);

            if ($event !== '') {
                $events[] = $event;
            }
        }

        return array_values(array_unique($events));
    }

    private static function legalDocuments(array $payload): array
    {
        $documents = [];

        foreach ($payload as $key => $value) {
            if (!is_string($key) || strpos($key, 'accept_') !== 0) {
                continue;
            }

            $docType = preg_replace('/[^a-z0-9_-]/', '', substr($key, 7)) ?? '';

            if ($docType === '') {
                continue;
            }

            $documents[$docType] = [
                'accepted' => self::boolean($value),
                'document_id' => (int) ($payload[$docType.'_id'] ?? 0),
            ];
        }

        if (!isset($documents['privacy_policy']) && array_key_exists('privacy', $payload)) {
            $documents['privacy_policy'] = [
                'accepted' => self::boolean($payload['privacy']),
                'document_id' => (int) ($payload['privacy_policy_id'] ?? 0),
            ];
        }

        if (!isset($documents['image_release']) && (array_key_exists('photo', $payload) || array_key_exists('photo_privacy', $payload))) {
            $documents['image_release'] = [
                'accepted' => self::boolean($payload['photo'] ?? ($payload['photo_privacy'] ?? false)),
                'document_id' => (int) ($payload['image_release_id'] ?? 0),
            ];
        }

        return $documents;
    }

    private static function metadata(array $payload): array
    {
        $knownKeys = [
            'participants',
            'name',
            'surname',
            'children_name',
            'children_surname',
            'children_dietary_requirements',
            'partner_name',
            'partner_surname',
            'contact_name',
            'contact_surname',
            'contact_phone',
            'phone',
            'cel',
            'contact_email',
            'email',
            'notes',
            'requests',
            'request',
            'events',
            'event',
            'event_key',
            'attendance_status',
            'locale',
            'lang',
            'privacy',
            'photo',
            'photo_privacy',
            'invite_code_id',
            'password_id',
            'source_url',
            'request_url',
            'post',
            'form',
            'g-recaptcha-token',
            'dietary_requirements',
            'allergies',
            'custom_fields',
        ];

        $metadata = [];

        foreach ($payload as $key => $value) {
            $key = (string) $key;

            if (
                in_array($key, $knownKeys, true)
                || strpos($key, 'accept_') === 0
                || preg_match('/^[a-z0-9_-]+_id$/i', $key) === 1
            ) {
                continue;
            }

            $metadata[$key] = $value;
        }

        return $metadata;
    }

    private static function attendanceStatus(array $payload, array $settings): string
    {
        if (!rsvpAttendanceStatusEnabled($settings)) {
            return 'confirmed';
        }

        return rsvpAttendanceStatusValue(
            $payload['attendance_status']
            ?? $payload['status']
            ?? $payload['attending']
            ?? null
        );
    }

    private static function customFieldColumns(array $payload): array
    {
        $schema = Response::customFieldDefinitions();
        $customPayloadRaw = $payload['custom_fields'] ?? [];

        if (is_string($customPayloadRaw) && self::isJsonArray($customPayloadRaw)) {
            $customPayloadRaw = json_decode($customPayloadRaw, true);
        }

        $customPayload = is_array($customPayloadRaw) ? $customPayloadRaw : [];
        $columns = [];

        foreach ($schema as $fieldKey => $definition) {
            $raw = $customPayload[$fieldKey] ?? ($payload[$fieldKey] ?? null);
            $columns[(string) $definition['column']] = rsvpCustomFieldValue($raw);
        }

        return $columns;
    }

    private static function inviteGroupCode(int $inviteCodeId): string
    {
        if ($inviteCodeId <= 0) {
            return '';
        }

        $session = InviteCodeSession::current();

        if (($session['id'] ?? 0) === $inviteCodeId) {
            return (string) ($session['invite_group_code'] ?? '');
        }

        $codeRow = InviteCode::find([
            'id' => $inviteCodeId,
            'deleted' => 'false',
        ], 1);

        if (!is_array($codeRow) || $codeRow === []) {
            return '';
        }

        $groupId = (int) ($codeRow['invite_group_id'] ?? 0);

        if ($groupId <= 0) {
            return '';
        }

        $groupRow = InviteGroup::find([
            'id' => $groupId,
            'deleted' => 'false',
        ], 1);

        return is_array($groupRow) && $groupRow !== []
            ? (string) ($groupRow['code'] ?? '')
            : '';
    }

    private static function authorizationCode(int $inviteCodeId): string
    {
        if ($inviteCodeId <= 0) {
            return '';
        }

        $session = InviteCodeSession::current();

        if (($session['id'] ?? 0) === $inviteCodeId) {
            return (string) ($session['authorization_code'] ?? '');
        }

        $codeRow = InviteCode::find([
            'id' => $inviteCodeId,
            'deleted' => 'false',
        ], 1);

        if (!is_array($codeRow) || $codeRow === []) {
            return '';
        }

        $authorizationId = (int) ($codeRow['authorization_id'] ?? 0);

        if ($authorizationId <= 0) {
            return '';
        }

        $authorizationRow = Authorization::find([
            'id' => $authorizationId,
            'deleted' => 'false',
        ], 1);

        return is_array($authorizationRow) && $authorizationRow !== []
            ? (string) ($authorizationRow['code'] ?? '')
            : '';
    }

    private static function string(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            if (is_array($payload[$key])) {
                continue;
            }

            $value = trim((string) $payload[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function boolean(mixed $value): bool
    {
        if (is_array($value)) {
            $value = $value[0] ?? false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function isJsonArray(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || ($value[0] !== '[' && $value[0] !== '{')) {
            return false;
        }

        json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
