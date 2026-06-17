<?php

use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\InviteCode;
use Wonder\Plugin\Rsvp\Models\InviteGroup;
use Wonder\Plugin\Rsvp\Models\Response;

if (!function_exists('rsvpInviteUsageMode')) {
    function rsvpInviteUsageMode(mixed $mode = null): array|object
    {
        $details = rsvpArrayDetail(
            rsvpInviteUsageModeDictionary(),
            is_scalar($mode) ? strtolower(trim((string) $mode)) : null
        );

        if (is_object($details) && $details->text === '') {
            $fallbackName = is_scalar($mode) ? trim((string) $mode) : '';
            $escapedText = htmlspecialchars($fallbackName !== '' ? ucfirst(str_replace('_', ' ', $fallbackName)) : '', ENT_QUOTES, 'UTF-8');

            $details->color = 'secondary';
            $details->bootstrapColor = 'secondary';
            $details->name = $fallbackName !== '' ? ucfirst(str_replace('_', ' ', $fallbackName)) : '';
            $details->text = $details->name;
            $details->classIcon = 'bi bi-ticket-perforated';
            $details->icon = "<i class='{$details->classIcon}'></i>";
            $details->tooltip = $details->text !== ''
                ? "<i class='{$details->classIcon}' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-title='{$escapedText}'></i>"
                : '';
            $details->badge = $details->name !== ''
                ? "<span class='badge text-bg-{$details->color}'>".strtoupper($details->name)."</span>"
                : '';
            $details->badgeTooltip = $details->text !== ''
                ? "<span class='badge text-bg-{$details->color}' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-title='{$escapedText}'>{$details->icon}</span>"
                : '';
            $details->badgeIcon = $details->badgeTooltip;
            $details->automaticResize = $details->name !== ''
                ? "<span class='badge text-bg-{$details->color}'><span class='pc-none'>{$details->icon}</span><span class='phone-none'>".strtoupper($details->name)."</span></span>"
                : '';
        }

        return $details;
    }
}

if (!function_exists('rsvpAttendanceStatusValue')) {
    function rsvpAttendanceStatusValue(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'declined', 'rejected', 'reject', 'no', 'false', '0' => 'declined',
            default => 'confirmed',
        };
    }
}

if (!function_exists('rsvpAttendanceStatusEnabled')) {
    function rsvpAttendanceStatusEnabled(array $settings): bool
    {
        return in_array(
            strtolower(trim((string) ($settings['enable_attendance_status'] ?? 'false'))),
            ['1', 'true', 'yes', 'on'],
            true
        );
    }
}

if (!function_exists('rsvpAttendanceStatusText')) {
    function rsvpAttendanceStatusText(mixed $value): string
    {
        return rsvpAttendanceStatusValue($value) === 'declined'
            ? __t('pages.rsvp.common.declined')
            : __t('pages.rsvp.common.confirmed');
    }
}

if (!function_exists('rsvpResponseAttendanceStatus')) {
    function rsvpResponseAttendanceStatus(mixed $value = null): array|object
    {
        $status = rsvpAttendanceStatusValue($value);

        $dictionary = rsvpAttendanceStatusDictionary();
        $dictionary['confirmed']['text'] = rsvpAttendanceStatusText('confirmed');
        $dictionary['declined']['text'] = rsvpAttendanceStatusText('declined');

        return rsvpArrayDetail($dictionary, $status);
    }
}

if (!function_exists('rsvpArrayDetail')) {
    function rsvpArrayDetail(array $items, mixed $key = null): array|object
    {
        $details = arrayDetails($items, $key);

        if (!is_object($details)) {
            return $details;
        }

        $details->bootstrapColor = (string) ($details->color ?? '');

        return $details;
    }
}

if (!function_exists('rsvpInviteUsageModeDictionary')) {
    function rsvpInviteUsageModeDictionary(): array
    {
        return [
            'single_use' => [
                'name' => 'Monouso',
                'text' => 'Monouso',
                'icon' => 'bi bi-1-circle',
                'color' => 'warning',
            ],
            'multiple_use' => [
                'name' => 'Multiuso',
                'text' => 'Multiuso',
                'icon' => 'bi bi-people',
                'color' => 'success',
            ],
        ];
    }
}

if (!function_exists('rsvpAttendanceStatusDictionary')) {
    function rsvpAttendanceStatusDictionary(): array
    {
        return [
            'confirmed' => [
                'name' => __t('pages.rsvp.common.confirmed'),
                'text' => __t('pages.rsvp.common.confirmed'),
                'icon' => 'bi bi-check2-circle',
                'color' => 'success',
            ],
            'declined' => [
                'name' => __t('pages.rsvp.common.declined'),
                'text' => __t('pages.rsvp.common.declined'),
                'icon' => 'bi bi-x-circle',
                'color' => 'danger',
            ],
        ];
    }
}

if (!function_exists('rsvpInviteGroupLabel')) {
    function rsvpInviteGroupLabel(mixed $groupId): string
    {
        $groupId = (int) $groupId;

        if ($groupId <= 0) {
            return '--';
        }

        $group = InviteGroup::find([ 'id' => $groupId ], 1);

        return is_array($group) && $group !== []
            ? (string) ($group['name'] ?? $group['code'] ?? '--')
            : '--';
    }
}

if (!function_exists('rsvpAuthorizationLabel')) {
    function rsvpAuthorizationLabel(mixed $authorizationId): string
    {
        $authorizationId = (int) $authorizationId;

        if ($authorizationId <= 0) {
            return '--';
        }

        $authorization = Authorization::find(['id' => $authorizationId], 1);

        return is_array($authorization) && $authorization !== []
            ? (string) ($authorization['name'] ?? $authorization['code'] ?? '--')
            : '--';
    }
}

if (!function_exists('rsvpInviteCodeResponses')) {
    function rsvpInviteCodeResponses(mixed $inviteCodeId): int
    {
        $inviteCodeId = (int) $inviteCodeId;

        if ($inviteCodeId <= 0) {
            return 0;
        }

        $result = Response::query()->Select(Response::$table, [
            'invite_code_id' => $inviteCodeId,
            'deleted' => 'false',
        ]);

        return (int) ($result->Nrow ?? 0);
    }
}

if (!function_exists('rsvpInviteGroupCodes')) {
    function rsvpInviteGroupCodes(mixed $groupId): int
    {
        $groupId = (int) $groupId;

        if ($groupId <= 0) {
            return 0;
        }

        $result = InviteCode::query()->Select(InviteCode::$table, [
            'invite_group_id' => $groupId,
            'deleted' => 'false',
        ]);

        return (int) ($result->Nrow ?? 0);
    }
}

if (!function_exists('rsvpDecodeJsonArray')) {
    function rsvpDecodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('rsvpEncodePrettyJson')) {
    function rsvpEncodePrettyJson(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return is_string($json) ? $json : '';
    }
}

if (!function_exists('rsvpDecodeJsonOrDefault')) {
    function rsvpDecodeJsonOrDefault(mixed $value, array $default = []): array
    {
        $decoded = rsvpDecodeJsonArray($value);

        return $decoded !== [] ? $decoded : $default;
    }
}

if (!function_exists('rsvpJsonPrettyList')) {
    function rsvpJsonPrettyList(mixed $value): string
    {
        $items = rsvpDecodeJsonArray($value);

        if ($items === []) {
            return '--';
        }

        $labels = array_values(array_filter(array_map(
            static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $items
        )));

        return $labels === [] ? '--' : implode(', ', $labels);
    }
}

if (!function_exists('rsvpResolveLocalizedValue')) {
    function rsvpResolveLocalizedValue(mixed $value, ?string $locale = null, string $fallbackLocale = 'it'): mixed
    {
        $locale = $locale !== null && trim($locale) !== ''
            ? trim($locale)
            : (trim((string) __l()) !== '' ? trim((string) __l()) : $fallbackLocale);

        if (!is_array($value)) {
            return $value;
        }

        $localizedKeys = [$locale, strtolower($locale), $fallbackLocale, 'default'];

        foreach ($localizedKeys as $key) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            $localized = $value[$key];

            if (!is_array($localized)) {
                return $localized;
            }
        }

        $isAssociative = array_keys($value) !== range(0, count($value) - 1);

        if (!$isAssociative) {
            return array_map(
                static fn ($item) => rsvpResolveLocalizedValue($item, $locale, $fallbackLocale),
                $value
            );
        }

        $resolved = [];

        foreach ($value as $key => $item) {
            $resolved[$key] = rsvpResolveLocalizedValue($item, $locale, $fallbackLocale);
        }

        return $resolved;
    }
}

if (!function_exists('rsvpMergeRecursive')) {
    function rsvpMergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
                && array_keys($base[$key]) !== range(0, count($base[$key]) - 1)
                && array_keys($value) !== range(0, count($value) - 1)
            ) {
                $base[$key] = rsvpMergeRecursive($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}

if (!function_exists('rsvpDatePart')) {
    function rsvpDatePart(?string $date, string $part): string
    {
        if ($date === null || trim($date) === '' || strtotime($date) === false) {
            return '';
        }

        if (function_exists('translateDate') && in_array($part, ['day', 'month'], true)) {
            return (string) translateDate($date, $part);
        }

        $timestamp = strtotime($date);

        return match ($part) {
            'day_number' => date('d', $timestamp),
            'time' => date('H:i', $timestamp),
            default => date('Y-m-d H:i', $timestamp),
        };
    }
}

if (!function_exists('rsvpParseListText')) {
    function rsvpParseListText(string $value): array
    {
        $value = str_replace(["\r\n", "\r", ';'], "\n", trim($value));

        if ($value === '') {
            return [];
        }

        $lines = preg_split('/[\n,]+/', $value) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line !== '') {
                $items[] = $line;
            }
        }

        return array_values(array_unique($items));
    }
}

if (!function_exists('rsvpFormatListText')) {
    function rsvpFormatListText(array $items): string
    {
        $items = array_values(array_filter(array_map(
            static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $items
        )));

        return implode("\n", $items);
    }
}

if (!function_exists('rsvpParseMapText')) {
    function rsvpParseMapText(string $value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($value)) ?: [];
        $map = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^([^:=]+)\s*[:=]\s*(.+)$/', $line, $matches) === 1) {
                $key = trim((string) $matches[1]);
                $label = trim((string) $matches[2]);
            } else {
                $key = $line;
                $label = $line;
            }

            if ($key !== '') {
                $map[$key] = $label !== '' ? $label : $key;
            }
        }

        return $map;
    }
}

if (!function_exists('rsvpFormatMapText')) {
    function rsvpFormatMapText(array $items): string
    {
        $lines = [];

        foreach ($items as $key => $label) {
            $key = trim((string) $key);
            $label = trim((string) $label);

            if ($key !== '') {
                $lines[] = $key.' = '.($label !== '' ? $label : $key);
            }
        }

        return implode("\n", $lines);
    }
}

if (!function_exists('rsvpEnsureJsonTextarea')) {
    function rsvpEnsureJsonTextarea(mixed $value, array $default = []): string
    {
        $array = rsvpDecodeJsonOrDefault($value, $default);

        return rsvpEncodePrettyJson($array);
    }
}

if (!function_exists('rsvpBooleanText')) {
    function rsvpBooleanText(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (is_bool($value)) {
            return $value
                ? __t('pages.rsvp.common.accepted')
                : __t('pages.rsvp.common.rejected');
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on'], true)
            ? __t('pages.rsvp.common.accepted')
            : __t('pages.rsvp.common.rejected');
    }
}

if (!function_exists('rsvpLegalDocumentLabel')) {
    function rsvpLegalDocumentLabel(string $docType): string
    {
        $docType = trim($docType);

        if ($docType === '') {
            return '';
        }

        return __t('legal.'.$docType.'.label');
    }
}

if (!function_exists('rsvpCustomFieldColumn')) {
    function rsvpCustomFieldColumn(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '';
        $key = trim($key, '_');

        if ($key === '') {
            $key = 'field';
        }

        return 'meta_'.$key;
    }
}

if (!function_exists('rsvpCustomFieldLabel')) {
    function rsvpCustomFieldLabel(array $field, ?string $fallbackKey = null): string
    {
        $label = trim((string) ($field['label'] ?? ''));

        if ($label !== '') {
            return $label;
        }

        return $fallbackKey !== null && trim($fallbackKey) !== ''
            ? trim($fallbackKey)
            : 'Campo personalizzato';
    }
}

if (!function_exists('rsvpCustomFieldValue')) {
    function rsvpCustomFieldValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $items = array_values(array_filter(array_map(
                static function ($item): string {
                    if (is_bool($item)) {
                        return $item ? 'true' : 'false';
                    }

                    return is_scalar($item) ? trim((string) $item) : '';
                },
                $value
            )));

            return $items === [] ? null : implode(', ', $items);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

if (!function_exists('rsvpRenderCustomFieldValue')) {
    function rsvpRenderCustomFieldValue(array $field, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $type = strtolower(trim((string) ($field['type'] ?? 'text')));
        $options = is_array($field['options'] ?? null) ? $field['options'] : [];
        $normalizedOptions = [];

        foreach ($options as $optionValue => $optionLabel) {
            $optionValue = trim((string) $optionValue);
            $optionLabel = trim((string) $optionLabel);

            if ($optionValue === '') {
                continue;
            }

            $normalizedOptions[$optionValue] = $optionLabel !== '' ? $optionLabel : $optionValue;
        }

        if (is_bool($value)) {
            return rsvpBooleanText($value);
        }

        if (is_array($value)) {
            $rawValues = array_map(
                static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                $value
            );
        } else {
            $stringValue = trim((string) $value);

            if ($stringValue === '') {
                return '';
            }

            $rawValues = in_array($type, ['select', 'checkbox'], true)
                ? preg_split('/\s*,\s*/', $stringValue) ?: []
                : [$stringValue];
        }

        $rawValues = array_values(array_filter($rawValues, static fn ($item) => $item !== ''));

        if ($rawValues === []) {
            return '';
        }

        if (in_array($type, ['select', 'checkbox', 'radio'], true)) {
            $rendered = array_map(
                static function (string $item) use ($normalizedOptions): string {
                    if (isset($normalizedOptions[$item])) {
                        return $normalizedOptions[$item];
                    }

                    if (in_array(strtolower($item), ['1', 'true', 'yes', 'on'], true)) {
                        return $normalizedOptions['true'] ?? $normalizedOptions['1'] ?? $item;
                    }

                    return $item;
                },
                $rawValues
            );

            return implode(', ', array_values(array_filter($rendered, static fn ($item) => trim($item) !== '')));
        }

        return implode(', ', $rawValues);
    }
}

if (!function_exists('rsvpMetadataSummary')) {
    function rsvpMetadataSummary(mixed $value): string
    {
        $metadata = rsvpDecodeJsonArray($value);

        if ($metadata === []) {
            return '';
        }

        $parts = [];

        foreach ($metadata as $key => $item) {
            $label = trim((string) $key);

            if ($label === '') {
                continue;
            }

            if (is_array($item)) {
                $normalized = [];

                foreach ($item as $nestedKey => $nestedValue) {
                    if (is_array($nestedValue)) {
                        $nestedValue = implode(', ', array_map('strval', $nestedValue));
                    } elseif (is_bool($nestedValue)) {
                        $nestedValue = rsvpBooleanText($nestedValue);
                    } elseif ($nestedValue === null) {
                        $nestedValue = '';
                    } else {
                        $nestedValue = (string) $nestedValue;
                    }

                    $nestedKey = trim((string) $nestedKey);
                    $nestedValue = trim((string) $nestedValue);

                    if ($nestedValue === '') {
                        continue;
                    }

                    $normalized[] = $nestedKey !== ''
                        ? $nestedKey.': '.$nestedValue
                        : $nestedValue;
                }

                $text = implode(', ', $normalized);
            } elseif (is_bool($item)) {
                $text = rsvpBooleanText($item);
            } elseif ($item === null) {
                $text = '';
            } else {
                $text = trim((string) $item);
            }

            if ($text !== '') {
                $parts[] = $label.': '.$text;
            }
        }

        return implode(' | ', $parts);
    }
}
