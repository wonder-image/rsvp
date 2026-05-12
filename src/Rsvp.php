<?php

namespace Wonder\Plugin\Rsvp;

use Wonder\App\Module\Contracts\ModuleInterface;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
use Wonder\Plugin\Rsvp\Support\FrontendContext;
use Wonder\Plugin\Rsvp\Support\FrontendPage;

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

    /**
     * Scaffold per nuove pagine RSVP-gated del consumer.
     *
     * Il consumer registra la route nel proprio
     * `custom/routes/route.frontend.php`, punta a un file handler in
     * `custom/pages/rsvp/<page>.php`, e quel file chiama Rsvp::renderPage()
     * con le sue opzioni. Il modulo gestisce: caricamento state,
     * redirect a /rsvp/login/ se serve codice invito, hook SEO
     * `RsvpExtension::seo($pageKey, $state)`, e include della view
     * dentro l'HTML scaffold di FrontendPage (head/body-start/header/
     * view/footer/body-end).
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
        $state = FrontendContext::state();

        if ($requireSession
            && !empty($state['requires_invite_code'])
            && (int) (($state['session'] ?? [])['id'] ?? 0) <= 0
        ) {
            $loginUrl = function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/';
            header('Location: '.$loginUrl, true, 302);
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

        FrontendPage::render($pageKey, $title, $description, $url, $viewPath, $data);
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
