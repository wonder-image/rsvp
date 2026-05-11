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

    public static function langPath(): string
    {
        return self::root().'/lang/';
    }

    public static function assetPath(string $path = ''): string
    {
        return self::root().'/resources/assets/'.ltrim($path, '/');
    }
}
