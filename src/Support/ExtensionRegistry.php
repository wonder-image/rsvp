<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\App\Module\StateRepository;
use Wonder\Plugin\Rsvp\Contracts\RsvpExtension;

/**
 * Risolve la classe RsvpExtension dichiarata dal consumer in
 * custom/config/modules.php.
 */
final class ExtensionRegistry
{
    private static ?RsvpExtension $instance = null;
    private static bool $resolved = false;

    public static function reset(): void
    {
        self::$instance = null;
        self::$resolved = false;
    }

    public static function get(): RsvpExtension
    {
        if (self::$resolved) {
            return self::$instance ?? new NullRsvpExtension();
        }

        self::$resolved = true;

        $config = StateRepository::config('rsvp');
        $className = trim((string) ($config['extension'] ?? ''));

        if ($className === '' || !class_exists($className)) {
            self::$instance = new NullRsvpExtension();
            return self::$instance;
        }

        $instance = new $className();

        if (!$instance instanceof RsvpExtension) {
            self::$instance = new NullRsvpExtension();
            return self::$instance;
        }

        self::$instance = $instance;
        return self::$instance;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        $raw = self::get()->fields();
        $normalized = [];

        foreach ($raw as $key => $def) {
            $key = trim((string) $key);

            if ($key === '' || !is_array($def)) {
                continue;
            }

            $type = strtolower(trim((string) ($def['type'] ?? 'text')));

            $normalized[$key] = [
                'type' => in_array($type, ['text', 'email', 'phone', 'number', 'textarea', 'select', 'checkbox'], true) ? $type : 'text',
                'label' => (string) ($def['label'] ?? $key),
                'options' => is_array($def['options'] ?? null) ? $def['options'] : [],
                'required' => (bool) ($def['required'] ?? false),
                'value' => $def['value'] ?? '',
            ];
        }

        return $normalized;
    }
}
