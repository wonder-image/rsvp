<?php

namespace Wonder\Plugin\Rsvp;

use Wonder\App\Module\Contracts\ModuleInterface;

final class Rsvp implements ModuleInterface
{
    public static function root(): string
    {
        return dirname(__DIR__);
    }

    public static function manifestPath(): string
    {
        return self::root().'/module.json';
    }

    public static function handlerPath(string $path): string
    {
        return self::root().'/handlers/'.ltrim($path, '/');
    }

    public static function viewPath(string $path): string
    {
        $path = ltrim($path, '/');
        $root = (string) ($GLOBALS['ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '');
        $override = $root !== ''
            ? $root.'/custom/modules/rsvp/views/'.$path
            : '';

        if ($override !== '' && file_exists($override)) {
            return $override;
        }

        return self::root().'/views/'.$path;
    }

    /**
     * Include un componente RSVP riutilizzabile.
     *
     * I componenti vivono in `views/components/<name>.php` del modulo e
     * sono overrideabili dal consumer in
     * `custom/modules/rsvp/views/components/<name>.php`.
     *
     * Gli `$args` vengono esposti al componente come variabile `$args`.
     * Tutte le variabili globali (es. $STATE, $PATH, $SOCIETY) restano
     * disponibili perché l'include avviene nello scope corrente.
     *
     * Esempio:
     *     <?= Rsvp::component('countdown', ['target_date' => '2026-06-02 18:30']) ?>
     */
    public static function component(string $name, array $args = []): void
    {
        $name = ltrim($name, '/');

        if (!str_ends_with($name, '.php')) {
            $name .= '.php';
        }

        $path = self::viewPath('components/'.$name);

        if (!is_file($path)) {
            return;
        }

        // I componenti si aspettano i legacy-globals del framework (SEO,
        // SOCIETY, PATH, PAGE_KEY, DB, ANALYTICS, ...) e l'output di
        // FrontendContext::state() in $STATE. Quando component() è
        // invocato come metodo statico, quelli sono globals che non
        // entrerebbero nello scope dell'include: importiamoli by-ref qui.
        if (class_exists(\Wonder\App\LegacyGlobals::class)) {
            foreach (\Wonder\App\LegacyGlobals::names() as $__legacyKey) {
                if (array_key_exists($__legacyKey, $GLOBALS)) {
                    $$__legacyKey = &$GLOBALS[$__legacyKey];
                }
            }
        }
        if (array_key_exists('STATE', $GLOBALS)) {
            $STATE = &$GLOBALS['STATE'];
        }

        include $path;
    }

    public static function langPath(): string
    {
        return self::root().'/lang/';
    }

    public static function assetPath(string $path = ''): string
    {
        return self::root().'/resources/assets/'.ltrim($path, '/');
    }
}
