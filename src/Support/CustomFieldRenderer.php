<?php

namespace Wonder\Plugin\Rsvp\Support;

final class CustomFieldRenderer
{
    public static function renderFrontend(string $key, array $definition): string
    {
        $type = strtolower(trim((string) ($definition['type'] ?? 'text')));
        $label = (string) ($definition['label'] ?? $key);
        $value = $definition['value'] ?? null;
        $required = !empty($definition['required']);
        $options = is_array($definition['options'] ?? null) ? $definition['options'] : [];
        $attribute = $required ? 'required' : '';
        $displayLabel = $required && !str_ends_with($label, '*') ? $label.'*' : $label;

        return match ($type) {
            'email' => email($label, $key, $value, $attribute),
            'phone' => phone($label, $key, $value, $attribute),
            'number' => number($label, $key, $value, $attribute),
            'textarea' => textarea($label, $key, $value, $attribute),
            'select' => select($label, $key, $options, $value, $attribute),
            'checkbox' => checkbox($displayLabel, $key, $options, 'checkbox', self::checkboxValue($value)),
            'radio' => checkbox($displayLabel, $key, $options, 'radio', self::radioValue($value)),
            'url' => function_exists('url') ? url($label, $key, $value, $attribute) : text($label, $key, $value, $attribute),
            default => text($label, $key, $value, $attribute),
        };
    }

    /**
     * @return array<int, string>
     */
    private static function checkboxValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn ($item): string => is_scalar($item) ? trim((string) $item) : '',
                $value
            ), static fn (string $item): bool => $item !== ''));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/\s*,\s*/', $value) ?: [],
            static fn (string $item): bool => $item !== ''
        ));
    }

    private static function radioValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
