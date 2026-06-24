<?php

namespace Wonder\Plugin\Rsvp;

use Wonder\App\Module\Contracts\ModuleInterface;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
use Wonder\View\View;

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

    public static function httpPath(string $path): string
    {
        return self::root().'/http/'.ltrim($path, '/');
    }

    public static function handlerPath(string $path): string
    {
        return self::httpPath($path);
    }

    public static function viewPath(string $path): string
    {
        $relative = ltrim($path, '/');
        $root = (string) ($GLOBALS['ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '');

        $slash = strpos($relative, '/');
        if ($root !== '' && $slash !== false) {
            $topDir = substr($relative, 0, $slash);
            $rest = substr($relative, $slash + 1);
            $override = $root.'/custom/view/'.$topDir.'/rsvp/'.$rest;
            if (is_file($override)) {
                return $override;
            }
        }

        return self::root().'/view/'.$relative;
    }

/**
     * Scaffold per nuove pagine RSVP-gated del consumer.
     *
     * Il consumer registra la route nel proprio
     * `custom/routes/route.frontend.php`, punta a un file handler in
     * `custom/http/frontend/<page>.php`, e quel file chiama Rsvp::renderPage()
     * con le sue opzioni. Il modulo gestisce: caricamento state,
     * redirect a /rsvp/login/ se serve codice invito, hook SEO
     * `RsvpExtension::seo($pageKey, $state)`, e render della pagina
     * tramite `View::make(...)->render()` dentro `frontend.main`.
     *
     * Config array:
     *   - 'key'           string   chiave logica della pagina, passata a
     *                                $PAGE_KEY e all'hook seo() (es. 'wishlist').
     *                                Default: 'rsvp.page'. Verrà prefissato 'rsvp.'
     *                                se non già presente.
     *   - 'view'          string   path ASSOLUTO del file di view che produce
     *                                il body della pagina. Obbligatorio.
     *   - 'title'         string   titolo di default (override-abile via
     *                                RsvpExtension::seo). Default: 'RSVP'.
     *   - 'description'   string   description di default. Default: ''.
     *   - 'url'           string   URL canonico (per SEO + breadcrumb).
     *                                Default: l'URL corrente.
     *   - 'require_session' bool   se true (default), redirect a login
     *                                quando `require_invite_code` è on e
     *                                non c'è sessione. Set false per
     *                                pagine pubbliche RSVP-themed.
     *   - 'data'          array    variabili extra passate al view (oltre
     *                                a $STATE già automatico). Default [].
     */
    public static function renderPage(array $config): void
    {
        $pageKey = trim((string) ($config['key'] ?? 'page'));
        $pageKey = str_starts_with($pageKey, 'rsvp.') ? $pageKey : 'rsvp.'.$pageKey;
        $shortKey = substr($pageKey, 5); // dopo "rsvp."

        $viewPath = (string) ($config['view'] ?? '');
        if ($viewPath === '' || !is_file($viewPath)) {
            throw new \RuntimeException("Rsvp::renderPage: 'view' obbligatorio e deve esistere ({$viewPath}).");
        }

        $requireSession = (bool) ($config['require_session'] ?? true);
        $state = require self::httpPath('frontend/context.php');

        if ($requireSession
            && !empty($state['requires_invite_code'])
            && (int) (($state['session'] ?? [])['id'] ?? 0) <= 0
        ) {
            header('Location: '.__r('rsvp.login'), true, 302);
            exit();
        }

        $title = (string) ($config['title'] ?? 'RSVP');
        $description = (string) ($config['description'] ?? '');
        $url = (string) ($config['url'] ?? ('https://'.($_SERVER['HTTP_HOST'] ?? '').($_SERVER['REQUEST_URI'] ?? '')));

        // Hook SEO dell'estensione (stesso meccanismo di home/login).
        $seoOverride = ExtensionRegistry::get()->seo($shortKey, $state);
        if (!empty($seoOverride['title'])) {
            $title = (string) $seoOverride['title'];
        }
        if (array_key_exists('description', $seoOverride)) {
            $description = (string) $seoOverride['description'];
        }
        if (!empty($seoOverride['image'])) {
            $GLOBALS['SEO'] = $GLOBALS['SEO'] ?? (object) [];
            $GLOBALS['SEO']->image = (string) $seoOverride['image'];
        }

        $data = is_array($config['data'] ?? null) ? $config['data'] : [];
        $data['STATE'] = $state;
        foreach ($data as $dataKey => $dataValue) {
            if (is_string($dataKey) && $dataKey !== '') {
                $GLOBALS[$dataKey] = $dataValue;
            }
        }

        $seo = $GLOBALS['SEO'] ?? (object) [];
        $seo->title = $title;
        $seo->description = $description;
        $seo->url = $url;
        $seo->breadcrumb = [$url => 'RSVP'];
        $GLOBALS['SEO'] = $seo;
        $GLOBALS['PAGE_KEY'] = $pageKey;

        View::make(self::viewPath('pages/frontend/render.php'), array_merge($data, [
            'PAGE_KEY' => $pageKey,
            'SEO_TITLE' => $title,
            'SEO_DESCRIPTION' => $description,
            'SEO_URL' => $url,
            'SEO_BREADCRUMB' => [$url => 'RSVP'],
            'SEO_IMAGE' => trim((string) ($seo->image ?? '')),
            'VIEW_PATH' => $viewPath,
            'GLOBALS_DATA' => $data,
        ]))->render();
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
