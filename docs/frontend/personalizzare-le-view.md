# Personalizzare le view

Il modulo è pensato per essere personalizzato dal sito **senza toccare
`vendor/`**. La personalizzazione del frontend avviene tramite l'override delle
view.

## Override delle view frontend

Se nel sito crei questi file, il modulo li userà al posto delle view del package:

```text
custom/view/pages/rsvp/frontend/home.php
custom/view/pages/rsvp/frontend/login.php
custom/view/components/rsvp/<nome>.php
```

È la meccanica corretta per cambiare HTML, struttura, sezioni, stile e logica
presentazionale. Le view del modulo restano come fallback.

## Contesto disponibile: `$STATE`

Le view ricevono `$STATE`, preparato dall'handler HTTP del modulo tramite
`http/frontend/context.php`. Chiavi principali:

| Chiave | Contenuto |
| --- | --- |
| `$STATE['settings']` | configurazione `rsvp/settings` |
| `$STATE['session']` | sessione invito corrente (`id`, `code`, `usage_mode`, `can_submit`, …) |
| `$STATE['authorization']` | autorizzazione del codice attivo |
| `$STATE['visible_events']` | eventi visibili per l'autorizzazione |
| `$STATE['featured_event']` | evento in evidenza |
| `$STATE['requires_invite_code']` | `true` se serve il login |
| `$STATE['max_participants']` | limite adulti effettivo |
| `$STATE['allow_children']` / `$STATE['max_children']` | regole bambini |
| `$STATE['require_image_release']` | liberatoria obbligatoria |
| `$STATE['custom_fields']` | custom field del contesto corrente |

Le view RSVP sono renderizzate **dentro il layout frontend del sito**, quindi
ereditano automaticamente `head.php`, `header.php`, `footer.php`, asset e utility
globali. Nelle override puoi usare `__l()`, `__t()` e `__r()` come in qualsiasi
altra pagina.

## Componenti riutilizzabili

Il modulo include componenti in `view/components/` (es. `countdown`,
`event-date`, `form`). Richiamali dalle view con il resolver del framework:

```php
<?= View::component('rsvp/countdown', [
    'target_date' => '2026-06-02 18:30',
    'state'       => $STATE,
]) ?>
```

Il modulo registra il namespace `rsvp` al boot; `View::component('rsvp/<nome>')`
risolve con la catena di override `custom/view/components/rsvp/<nome>.php` →
view del modulo. Gli `$args` sono esposti al componente come `$args`; i
legacy-globals del framework e `$STATE` restano disponibili nello scope del
componente.

## Slot del form

Il componente `rsvp/form` supporta slot nominati per iniettare contenuto
senza sovrascrivere l'intera view:

```php
<?= View::component('rsvp/form', [
    'state' => $STATE,
    'slots' => [
        'before_fields' => fn () => View::component('rsvp/event-date', ['state' => $STATE]),
        'extra_cta'     => '<p class="text-small">Ti aspettiamo!</p>',
    ],
]) ?>
```

Slot disponibili:

- `before_fields` — prima dei campi del form
- `after_fields` — dopo i campi del form
- `before_submit` — prima del pulsante di invio
- `extra_cta` — call-to-action aggiuntiva

Ogni slot accetta una stringa HTML o un callable che ritorna una stringa.

## Scaffolding degli override

Il comando forge `module:publish` copia le view del modulo negli override del
sito, pronte per la personalizzazione:

```bash
# pubblica tutte le view del modulo
php forge module:publish rsvp

# pubblica un singolo file (sovrascrive se già presente)
php forge module:publish rsvp --only=components/form.php --force
```

## Pagine RSVP aggiuntive

Per creare nuove pagine RSVP-gated del sito (es. una wishlist), registra una
route in `custom/routes/route.frontend.php`, punta a un file in `custom/http/...`
e usa `Rsvp::renderPage()`:

```php
<?php

use Wonder\Plugin\Rsvp\Rsvp;

Rsvp::renderPage([
    'key'             => 'wishlist',                 // diventa $PAGE_KEY = 'rsvp.wishlist'
    'view'            => $ROOT . '/custom/view/pages/frontend/wishlist.php',
    'title'           => 'Lista regali',
    'require_session' => true,                        // redirect a login se serve il codice
    'data'            => ['extra' => '...'],          // variabili extra per la view ($STATE è automatico)
]);
```

`renderPage()` carica lo stato, gestisce il redirect al login quando serve,
applica l'hook SEO dell'estensione e renderizza la view nel layout
`frontend.main`.

La view passata in `view` deve contenere solo il body della pagina: non deve
richiamare di nuovo `View::layout()` o impostare manualmente il layout.
