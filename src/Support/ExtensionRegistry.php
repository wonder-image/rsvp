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
     * Custom field del contesto corrente come `FormField` pronti al render,
     * usati dal form frontend (`$input->render()`).
     *
     * @return array<int, \Wonder\App\ResourceSchema\FormField>
     */
    public static function inputs(): array
    {
        return CustomFieldRegistry::visibleInputs(self::get());
    }

    /**
     * Definizioni normalizzate dei field del contesto corrente
     * (chiave => type/label/options/required/value). Usato per validazione.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        return CustomFieldRegistry::visibleDefinitions(self::get());
    }

    /**
     * SUPER-SET di tutti i campi possibili, indipendente dalla sessione.
     * Usato dallo schema sync (Response::tableSchema) per creare tutte
     * le colonne `meta_<key>` necessarie.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function allFields(): array
    {
        return CustomFieldRegistry::allDefinitions(self::get());
    }
}
