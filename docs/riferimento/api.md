# API

## Invio risposta RSVP

La submission del form usa la **rotta resource standard**
`api.resource.rsvp-responses.store` (`POST /api/resource/rsvp-responses/`),
servita da `ResponseResource`. Il componente
[`form.php`](../frontend/personalizzare-le-view.md) la invoca via `formSubmit()`:

```php
formSubmit(this.form, '<?= __e('api.resource.rsvp-responses.store') ?>', callback)
```

Normalizzazione del payload, validazione RSVP-specifica (codice invito,
single-use, consensi, custom field obbligatori) e notifiche email vivono in
`ResponseResource::mutateRequestValues()` / `afterStore()`. Gli errori di
validazione tornano come `EndpointException(422)` e il frontend li mostra nel
risultato del loader.

## Endpoint di sessione

Questi endpoint restano **fissi e cross-lingua** (non tradotti), registrati in
`config/routes/route.api.php`:

| Route name | Metodo e path | Scopo |
| --- | --- | --- |
| `api.rsvp.login` | `POST /api/rsvp/login/` | apertura sessione con codice invito |
| `api.rsvp.logout` | `POST /api/rsvp/logout/` | chiusura sessione |
| `api.rsvp.session` | `GET /api/rsvp/session/` | stato della sessione corrente |

Risposta in formato JSON.

## Payload di invio

Campi supportati direttamente:

* `participants` — array di `{ name, surname, dietary_requirements, is_child }`
* `contact_name`, `contact_surname`, `contact_phone`, `contact_email`
* `event_key`, `events`
* `notes`
* `accept_privacy_policy` + `privacy_policy_id`
* `accept_image_release` + `image_release_id`
* `invite_code_id`
* `request_url` (accetta anche l'alias legacy `source_url`)
* i custom field dell'estensione (per chiave, anche annidati in `custom_fields`)

Il normalizzatore accetta anche varianti legacy dei nomi campo (`name`,
`surname`, `children_*`, `partner_*`, `phone`, `cel`, `email`, `event`,
`allergies`, `requests`/`request`, `lang`, `photo_privacy`, `password_id`, oltre
a un blocco `form` JSON). I campi extra non riconosciuti e non dichiarati
nell'estensione confluiscono in `metadata_json`.

## Login (`api.rsvp.login`)

Accetta il codice invito e apre la sessione RSVP. In caso di codice mancante,
non valido, disattivato o non autorizzato per l'area, l'API risponde con un
errore uniforme (codice `905`, "Password errata" nelle traduzioni del framework),
così l'UX lato client resta coerente.

## Sessione (`api.rsvp.session`)

Ritorna lo stato della sessione corrente: `id`, `code`, `usage_mode`,
`usage_count`, `can_submit`, `authorization_code`, `invite_group_code`. Le view
e il frontend lo usano per sapere se l'utente può inviare e quante volte.

## Helper route

Nei template del sito costruisci gli URL con `__r()` (route di sessione) o
`__e()` (URL per JS, es. la submission):

```php
<a href="<?= __r('rsvp.login') ?>">Accedi</a>
formSubmit(this.form, '<?= __e('api.resource.rsvp-responses.store') ?>', cb);
```
