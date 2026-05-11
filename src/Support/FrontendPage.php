<?php

namespace Wonder\Plugin\Rsvp\Support;

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
        $ROOT = (string) ($GLOBALS['ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '');
        $ROOT_APP = (string) ($GLOBALS['ROOT_APP'] ?? '');
        $SEO = $GLOBALS['SEO'] ?? (object) [];

        $SEO->title = $title;
        $SEO->description = $description;
        $SEO->url = $url;
        $SEO->breadcrumb = [$url => 'RSVP'];

        $GLOBALS['SEO'] = $SEO;
        $GLOBALS['PAGE_KEY'] = $pageKey;

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
