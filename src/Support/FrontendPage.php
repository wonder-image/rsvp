<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\App\LegacyGlobals;

final class FrontendPage
{
    public static function render(
        string $pageKey,
        string $title,
        string $description,
        string $url,
        string $viewPath,
        array $data = []
    ): void {
        // Importa i legacy-globals (SEO, SOCIETY, PATH, DB, ANALYTICS, ...)
        // come variabili locali by-ref, così head.php/header.php/footer.php
        // del consumer e del framework li trovano in scope quando inclusi
        // da dentro questo metodo statico.
        if (class_exists(LegacyGlobals::class)) {
            foreach (LegacyGlobals::names() as $__legacyKey) {
                if (array_key_exists($__legacyKey, $GLOBALS)) {
                    $$__legacyKey = &$GLOBALS[$__legacyKey];
                }
            }
        }

        foreach ([
            'ACTIVE_STATISTICS',
            'VISITOR_ID',
            'SESSION_ID',
            'REGISTERED_USER',
            'USER_ID',
            'NAV_BACKEND',
            'PAGE_KEY',
        ] as $__extraKey) {
            if (array_key_exists($__extraKey, $GLOBALS)) {
                $$__extraKey = &$GLOBALS[$__extraKey];
            }
        }

        $ROOT = (string) ($GLOBALS['ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '');
        $ROOT_APP = (string) ($GLOBALS['ROOT_APP'] ?? '');
        $SEO = $GLOBALS['SEO'] ?? (object) [];

        $SEO->title = $title;
        $SEO->description = $description;
        $SEO->url = $url;
        $SEO->breadcrumb = [$url => 'RSVP'];

        $GLOBALS['SEO'] = $SEO;
        $GLOBALS['PAGE_KEY'] = $pageKey;
        $PAGE_KEY = $pageKey;

        extract($data, EXTR_SKIP);

        ?>
        <!DOCTYPE html>
        <html lang="<?=htmlspecialchars((string) __l(), ENT_QUOTES, 'UTF-8')?>">
        <head>
            <?php include $ROOT_APP.'/utility/frontend/head.php'; ?>
        </head>
        <body>
            <?php include $ROOT_APP.'/utility/frontend/body-start.php'; ?>
            <?php include $ROOT.'/custom/utility/frontend/header.php'; ?>

            <?php include $viewPath; ?>

            <?php include $ROOT.'/custom/utility/frontend/footer.php'; ?>
            <?php include $ROOT_APP.'/utility/frontend/body-end.php'; ?>
        </body>
        </html>
        <?php
    }
}
