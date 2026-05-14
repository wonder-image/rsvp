<?php

namespace Wonder\Plugin\Rsvp\Support;

use ReflectionException;
use ReflectionMethod;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\Plugin\Rsvp\Contracts\RsvpExtension;

final class CustomFieldRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function visibleDefinitions(RsvpExtension $extension): array
    {
        rsvp_boot_translations();

        try {
            if (self::hasCustomFormInputs($extension, 'formInputs')) {
                /** @var array<int, FormInput> $formInputs */
                $formInputs = $extension->formInputs();

                return self::normalizeFormInputs($formInputs);
            }

            if (self::hasCustomFormInputs($extension, 'allFormInputs')) {
                /** @var array<int, FormInput> $formInputs */
                $formInputs = $extension->allFormInputs();

                return self::normalizeFormInputs($formInputs);
            }

            return self::normalizeLegacyDefinitions($extension->fields());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function allDefinitions(RsvpExtension $extension): array
    {
        rsvp_boot_translations();

        try {
            if (self::hasCustomFormInputs($extension, 'allFormInputs')) {
                /** @var array<int, FormInput> $formInputs */
                $formInputs = $extension->allFormInputs();

                return self::normalizeFormInputs($formInputs);
            }

            if (self::hasCustomFormInputs($extension, 'formInputs')) {
                /** @var array<int, FormInput> $formInputs */
                $formInputs = $extension->formInputs();

                return self::normalizeFormInputs($formInputs);
            }

            return self::normalizeLegacyDefinitions($extension->allFields());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, array<string, mixed>> $raw
     * @return array<string, array<string, mixed>>
     */
    public static function normalizeLegacyDefinitions(array $raw): array
    {
        $normalized = [];

        foreach ($raw as $key => $definition) {
            $key = trim((string) $key);

            if ($key === '' || !is_array($definition)) {
                continue;
            }

            $normalized[$key] = self::normalizeDefinition($key, $definition['type'] ?? 'text', [
                'label' => $definition['label'] ?? null,
                'options' => $definition['options'] ?? [],
                'required' => (bool) ($definition['required'] ?? false),
                'value' => $definition['value'] ?? '',
            ]);
        }

        return $normalized;
    }

    /**
     * @param array<int, FormInput> $inputs
     * @return array<string, array<string, mixed>>
     */
    public static function normalizeFormInputs(array $inputs): array
    {
        $normalized = [];

        foreach ($inputs as $input) {
            if (!$input instanceof FormInput) {
                continue;
            }

            $key = trim((string) $input->name);

            if ($key === '') {
                continue;
            }

            $attribute = trim((string) ($input->get('attribute') ?? ''));

            $normalized[$key] = self::normalizeDefinition($key, $input->get('helper') ?? 'text', [
                'label' => $input->get('label'),
                'options' => $input->get('options') ?? [],
                'required' => str_contains($attribute, 'required'),
                'value' => $input->get('value'),
            ]);
        }

        return $normalized;
    }

    private static function hasCustomFormInputs(RsvpExtension $extension, string $method): bool
    {
        if (!method_exists($extension, $method)) {
            return false;
        }

        try {
            $reflection = new ReflectionMethod($extension, $method);
        } catch (ReflectionException) {
            return false;
        }

        if (!$reflection->isPublic()) {
            return false;
        }

        return $reflection->getDeclaringClass()->getName() !== AbstractRsvpExtension::class;
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private static function normalizeDefinition(string $key, mixed $type, array $schema): array
    {
        $type = self::normalizeType($type);

        return [
            'type' => $type,
            'label' => self::normalizeLabel($schema['label'] ?? null, $key),
            'options' => self::normalizeOptions($schema['options'] ?? [], $type),
            'required' => (bool) ($schema['required'] ?? false),
            'value' => $schema['value'] ?? '',
        ];
    }

    private static function normalizeType(mixed $type): string
    {
        $type = strtolower(trim((string) $type));

        return match ($type) {
            'tel', 'phone' => 'phone',
            'email' => 'email',
            'number' => 'number',
            'textarea' => 'textarea',
            'select', 'selectsearch' => 'select',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'url' => 'url',
            default => 'text',
        };
    }

    private static function normalizeLabel(mixed $label, string $fallbackKey): string
    {
        $label = trim((string) $label);

        return $label !== '' ? $label : $fallbackKey;
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeOptions(mixed $options, string $type): array
    {
        if (!in_array($type, ['select', 'checkbox', 'radio'], true) || !is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $value => $label) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (is_array($label)) {
                $label = $label['label'] ?? $label['name'] ?? $value;
            }

            if (!is_scalar($label) && $label !== null) {
                $label = $value;
            }

            $label = trim((string) ($label ?? $value));
            $normalized[$value] = $label !== '' ? $label : $value;
        }

        return $normalized;
    }
}
