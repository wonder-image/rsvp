# Route e flusso utente

Il modulo registra due pagine frontend tramite
`config/routes/route.frontend.php`.

## Route frontend

| Route name | Path canonico | Pagina |
| --- | --- | --- |
| `rsvp.home` | `GET /rsvp/` | form di conferma partecipazione |
| `rsvp.login` | `GET /rsvp/login/` | accesso con codice invito |

I path sono **traducibili** via `lang/{locale}/urls.json`. Di default:

* IT — home `/rsvp/`, login `/rsvp/accedi/`
* EN — home `/rsvp/`, login `/rsvp/login/`

{% hint style="warning" %}
Nei template del sito **non scrivere path hardcoded**: usa sempre gli helper
route, che risolvono la lingua attiva e sopravvivono ai rename.

```php
<a href="<?= __r('rsvp.home') ?>">Vai al form RSVP</a>
<a href="<?= __r('rsvp.login') ?>">Accedi</a>
```
{% endhint %}

## Flusso utente

Con `require_invite_code = true`:

1. l'utente apre `rsvp.login`;
2. inserisce un codice invito valido;
3. il modulo apre una sessione RSVP (`InviteCodeSession`) ed emette un
   cookie remember-me firmato (12 mesi): alle visite successive la sessione
   viene ripristinata senza reinserire il codice;
4. l'utente entra in `rsvp.home` e compila il form.

Se l'utente apre `rsvp.home` senza sessione valida (né cookie remember-me),
l'handler lo reindirizza automaticamente a `rsvp.login`.

Il cookie viene emesso **solo** dopo un login riuscito con codice digitato:
avere il link di `rsvp.login` non basta per entrare. Disattivare o cancellare
il codice dal backend revoca anche l'accesso via cookie.

Con `require_invite_code = false`, `rsvp.home` è accessibile direttamente e la
sessione invito non è richiesta.

## Codici monouso

Un `rsvp_invite_code` con `usage_mode = single_use` consente un solo invio: dopo
la prima risposta, lo stato di sessione (`can_submit`) impedisce un secondo
invio. I codici `multiple_use` non hanno questo limite.

## Stato della pagina (`$STATE`)

Entrambe le pagine ricevono dal modulo un array di stato preparato da
`Rsvp::context()` (il provider in `context.php`) ed esposto alle view come
`$STATE`. Contiene
impostazioni, sessione, autorizzazione, eventi visibili, limiti, contenuti e
custom field. L'elenco completo delle chiavi è in
[Personalizzare le view](personalizzare-le-view.md).
