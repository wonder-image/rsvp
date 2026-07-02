<?php

namespace Wonder\Plugin\Rsvp;

use Wonder\App\Module\Contracts\ModuleInterface;
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

    /**
     * Path del provider di stato RSVP (non è un handler http).
     */
    public static function contextPath(): string
    {
        return self::root().'/context.php';
    }

    /**
     * Stato RSVP condiviso tra pagine, handler API e resource.
     *
     * Carica `context.php` e ne ritorna l'array di stato (sessione, eventi
     * visibili, settings, max partecipanti/bambini, custom fields, ...).
     *
     * Senza argomenti la sessione corrente viene risolta da
     * `InviteCodeSession::current()` e il risultato è memoizzato per la
     * richiesta (evita query duplicate). Passando una `$session` esplicita
     * (es. subito dopo login/logout) si forza quello stato e si bypassa la
     * cache, perché lo stato dipende dalla sessione fornita dal chiamante.
     *
     * @param mixed $session sessione esplicita; `null` = usa quella corrente.
     * @return array<string, mixed>
     */
    public static function context(mixed $session = null): array
    {
        $path = self::contextPath();

        if ($session !== null) {
            return (static function (mixed $session) use ($path): array {
                return require $path;
            })($session);
        }

        static $cached = null;

        if ($cached === null) {
            $cached = (static function () use ($path): array {
                $session = null;

                return require $path;
            })();
        }

        return $cached;
    }

    /**
     * URL pubblico della locandina caricata nelle impostazioni RSVP
     * (campo `poster`), da usare come immagine SEO di default.
     * Stringa vuota se non caricata.
     */
    public static function posterUrl(): string
    {
        return trim((string) ((self::context()['settings'] ?? [])['poster_url'] ?? ''));
    }

    /**
     * Gate di accesso RSVP.
     *
     * Se l'RSVP richiede un codice invito e non c'è una sessione attiva,
     * redirige alla pagina di login ed esce; altrimenti non fa nulla.
     * Basato sullo stato condiviso di `Rsvp::context()`.
     */
    public static function auth(): void
    {
        $context = self::context();

        if (!empty($context['requires_invite_code'])
            && (int) (($context['session'] ?? [])['id'] ?? 0) <= 0
        ) {
            header('Location: '.__r('rsvp.login'), true, 302);
            exit();
        }
    }

    public static function viewPath(string $path): string
    {
        $path = ltrim($path, '/');
        $root = (string) ($GLOBALS['ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '');
        $override = $root !== ''
            ? $root.'/custom/modules/rsvp/view/'.$path
            : '';

        if ($override !== '' && file_exists($override)) {
            return $override;
        }

        return self::root().'/view/'.$path;
    }

    /**
     * Include un componente RSVP riutilizzabile.
     *
     * I componenti vivono in `view/components/<name>.php` del modulo e
     * sono overrideabili dal consumer in
     * `custom/modules/rsvp/view/components/<name>.php`.
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
        // SOCIETY, PATH, PAGE_KEY, DB, ANALYTICS, ...) e $STATE. Quando
        // component() è invocato come metodo statico, quelli sono globals
        // che non entrerebbero nello scope dell'include: importiamoli by-ref qui.
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
     * Apre un layout frontend del modulo RSVP.
     *
     * Mappa il nome corto sul file `view/layout/frontend/rsvp.<name>.php`
     * (es. `Rsvp::layout('main')` → `rsvp.main.php`,
     * `Rsvp::layout('auth')` → `rsvp.auth.php`). Il path passa da
     * `viewPath()`, quindi supporta l'override del consumer in
     * `custom/modules/rsvp/view/layout/frontend/rsvp.<name>.php`, esattamente
     * come i componenti.
     *
     * `$data` viene inoltrato a `View::layout()` (es. id/title/subtitle del
     * layout `auth`) ed è disponibile nel layout.
     *
     * Esempio:
     *     <?php Rsvp::layout('main'); ?>
     *     ... body ...
     *     <?php \Wonder\View\View::end(); ?>
     */
    public static function layout(string $name, array $data = []): void
    {
        $name = ltrim($name, '/');

        if (str_ends_with($name, '.php')) {
            $name = substr($name, 0, -4);
        }

        View::layout(self::viewPath('layout/frontend/rsvp.'.$name.'.php'), $data);
    }

    /**
     * Scaffold per nuove pagine RSVP-gated del consumer.
     *
     * Il consumer registra la route nel proprio
     * `custom/routes/route.frontend.php`, punta a un file handler in
     * `custom/http/frontend/<page>.php`, e quel file chiama Rsvp::renderPage()
     * con le sue opzioni. Il modulo gestisce: caricamento state via
     * `Rsvp::context()`, gate di accesso via `Rsvp::auth()` (redirect a
     * /rsvp/login/ se serve un codice invito), SEO da `$config`, e render
     * della pagina tramite `View::make(...)->render()` dentro `rsvp.main`.
     *
     * Config array:
     *   - 'key'           string   chiave logica della pagina, passata a
     *                                $PAGE_KEY (es. 'wishlist').
     *                                Default: 'rsvp.page'. Verrà prefissato 'rsvp.'
     *                                se non già presente.
     *   - 'view'          string   path ASSOLUTO del file di view che produce
     *                                il body della pagina. Obbligatorio.
     *   - 'title'         string   titolo SEO della pagina. Default: 'RSVP'.
     *   - 'description'   string   description di default. Default: ''.
     *   - 'image'         string   URL immagine SEO (og:image). Default:
     *                                la locandina caricata nelle impostazioni
     *                                RSVP (`poster`), se presente.
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

        $viewPath = (string) ($config['view'] ?? '');
        if ($viewPath === '' || !is_file($viewPath)) {
            throw new \RuntimeException("Rsvp::renderPage: 'view' obbligatorio e deve esistere ({$viewPath}).");
        }

        if ((bool) ($config['require_session'] ?? true)) {
            self::auth();
        }

        $state = self::context();

        $title = (string) ($config['title'] ?? 'RSVP');
        $description = (string) ($config['description'] ?? '');
        $url = (string) ($config['url'] ?? ('https://'.($_SERVER['HTTP_HOST'] ?? '').($_SERVER['REQUEST_URI'] ?? '')));

        $data = is_array($config['data'] ?? null) ? $config['data'] : [];
        $data['STATE'] = $state;
        foreach ($data as $dataKey => $dataValue) {
            if (is_string($dataKey) && $dataKey !== '') {
                $GLOBALS[$dataKey] = $dataValue;
            }
        }

        $image = trim((string) ($config['image'] ?? ''));

        if ($image === '') {
            $image = self::posterUrl();
        }

        $seo = $GLOBALS['SEO'] ?? (object) [];
        $seo->title = $title;
        $seo->description = $description;
        $seo->url = $url;
        $seo->breadcrumb = [];

        if ($image !== '') {
            $seo->image = $image;
        }
        $GLOBALS['SEO'] = $seo;
        $GLOBALS['PAGE_KEY'] = $pageKey;

        // Wrappa il body della pagina consumer (solo markup) nel layout
        // principale del modulo, senza file scaffold intermedio.
        self::layout('main');
        View::make($viewPath, array_merge($data, ['PAGE_KEY' => $pageKey]))->render();
        View::end();
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
