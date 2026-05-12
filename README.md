# wonder-image/rsvp

Installazione del modulo via Packagist:

```bash
composer require wonder-image/rsvp:dev-main
```

Modulo RSVP per `wonder-image/app`, pronto per `wonder-image/new-site`.

## Installazione

1. Installa il package con Composer.
2. Abilita il modulo in `custom/config/modules.php`.

```php
<?php

return [
    'rsvp' => true,
];
```

3. Esegui l’update del progetto consumer per materializzare le tabelle del modulo.

Non servono `repositories` custom nel `composer.json` del consumer se il package viene installato da Packagist.

## URL frontend

Le due pagine frontend del modulo sono:

- `rsvp.home`
- `rsvp.login`

Path canonical:

- `GET /rsvp/`
- `GET /rsvp/login/`

Traduzioni URL incluse nel package:

- IT:
  - home: `/rsvp/`
  - login: `/rsvp/accedi/`
- EN:
  - home: `/rsvp/`
  - login: `/rsvp/login/`

Nei template del progetto host usa sempre gli helper route:

```php
<?=__r('rsvp.home')?>
<?=__r('rsvp.login')?>
```

## Flusso frontend

Se `require_invite_code = true`:

1. l’utente apre `rsvp.login`
2. inserisce un codice invito valido
3. il modulo apre una sessione RSVP
4. l’utente entra in `rsvp.home`

Se `require_invite_code = false`, `rsvp.home` è accessibile direttamente.

## API

Route name API:

- `api.rsvp.submit`
- `api.rsvp.login`
- `api.rsvp.logout`
- `api.rsvp.session`

Path API:

- `POST /api/rsvp/`
- `POST /api/rsvp/login/`
- `POST /api/rsvp/logout/`
- `GET /api/rsvp/session/`

Le API restano intenzionalmente fisse e cross-lingua.

## Backend del modulo

Dopo l’attivazione trovi queste resource:

- `rsvp/settings`
- `rsvp/events`
- `rsvp/authorizations`
- `rsvp/invite-codes`
- `rsvp/invite-groups`
- `rsvp/responses`

## Permessi backend

Il modulo registra un permesso custom backend:

- `rsvp_response_viewer`

Questo permesso serve per dare accesso solo alla lettura delle risposte RSVP.

Un utente con `rsvp_response_viewer` può:

- vedere la voce backend `rsvp/responses`
- aprire lista risposte
- aprire dettaglio risposta
- esportare le risposte

Un utente con `rsvp_response_viewer` non può:

- modificare impostazioni RSVP
- gestire eventi RSVP
- gestire autorizzazioni
- gestire codici invito
- gestire gruppi invito
- cancellare risposte

La cancellazione delle risposte resta riservata ad `admin`.

## Come funziona il dominio RSVP

### `rsvp_event`

Definisce un evento selezionabile nel form RSVP.

Campi principali:

- `code`
- `name`
- `starts_at`
- `location_name`
- `location_address`
- `location_address_url`
- `location_position_url`
- `location_logo`
- `position`
- `active`

Usalo per casi come:

- matrimonio
- aperitivo
- cena
- festa aziendale
- welcome party

### `rsvp_authorization`

Definisce cosa un invitato può vedere o selezionare.

Campi principali:

- `visible_event_keys_json`
- `max_participants`
- `allow_children`
- `max_children`

Usa una `authorization` quando codici diversi devono avere regole diverse. Esempio:

- `WEDDING_ONLY`
- `WEDDING_AND_APERITIF`
- `CORPORATE_GUEST`

### `rsvp_invite_code`

È il codice effettivamente inserito dall’utente nella pagina login.

Campi principali:

- `code`
- `usage_mode`: `single_use` o `multiple_use`
- `authorization_id`
- `invite_group_id`
- `active`

Il codice:

- apre la sessione RSVP
- eredita le regole dalla sua `authorization`
- può essere monouso o multiuso
- può essere organizzato in un gruppo invito

### `rsvp_invite_group`

È un contenitore organizzativo. Non governa il frontend da solo.

Serve per segmentazioni come:

- famiglia
- colleghi
- fornitori
- tavoli
- lato sposa / lato sposo

Le regole di visibilità restano nella `authorization`.

## Flusso consigliato di configurazione

1. configura `rsvp/settings`
2. crea gli eventi in `rsvp/events`
3. crea una o più `rsvp/authorizations`
4. crea eventuali `rsvp/invite-groups`
5. crea i `rsvp/invite-codes`
6. condividi `rsvp.login` o `rsvp.home` a seconda del caso

Esempio:

- evento `WEDDING`
- evento `APERITIF`
- autorizzazione `WEDDING_ONLY` con `visible_event_keys_json = ["WEDDING"]`
- autorizzazione `WEDDING_AND_APERITIF` con `visible_event_keys_json = ["WEDDING", "APERITIF"]`
- gruppo `FAMIGLIA_ROSSI`
- codice `ROSSI01` collegato a `FAMIGLIA_ROSSI` e `WEDDING_AND_APERITIF`

## Cosa configuri in `rsvp/settings`

`rsvp/settings` contiene solo la configurazione operativa del modulo:

- `require_invite_code`
- `login_title`
- `login_text`
- `max_participants`
- `allow_children`
- `max_children`
- `require_image_release`
- `admin_email`
- `admin_notifications`
- `customer_notifications`
- `customer_subject`
- `customer_message`
- `admin_subject`
- `admin_message`

Non ci sono più in `settings`:

- `Titolo RSVP`
- `Testo RSVP`
- `Catalogo eventi`
- `Catalogo eventi esteso`
- `Contenuti pagina multilingua`

Gli eventi stanno in `rsvp/events`. La personalizzazione strutturale del frontend si fa nelle view override del progetto host.

## Default email inclusi nel codice

Il modulo include fallback reali per le email, usati quando i campi in `rsvp/settings` sono vuoti.

Default ospite:

- oggetto: `Conferma RSVP ricevuta`
- contenuto: conferma della ricezione con riepilogo RSVP

Default admin:

- oggetto: `Nuova risposta RSVP - {{contact_name}} {{contact_surname}}`
- contenuto: riepilogo completo RSVP con link al dettaglio backend

Placeholder supportati nei messaggi:

- `{{contact_name}}`
- `{{contact_surname}}`
- `{{event_name}}`
- `{{event_starts_at}}`
- `{{summary_html}}`
- `{{response_url}}`

## Export risposte

L’export RSVP genera una riga per ogni partecipante, non una sola riga per referente.

Per rendere leggibili le prenotazioni multiple, ogni risposta ha un codice prenotazione:

- formato: `pre_0000001`
- salvato nel campo `booking_code`
- usato anche come fallback calcolato da `id` per le risposte più vecchie

Nell’export troverai quindi:

- il referente
- una riga per ciascun partecipante
- il codice prenotazione ripetuto su tutte le righe della stessa prenotazione

## Come customizzare le view dopo l’installazione

Il modulo supporta override view dal consumer.

Se nel progetto host crei questi file, il modulo userà quelli al posto delle view del package:

- `custom/modules/rsvp/views/frontend/home.php`
- `custom/modules/rsvp/views/frontend/login.php`

Questa è la meccanica corretta per personalizzare HTML, struttura, sezioni, stile e logica presentazionale senza toccare `vendor/`.

Le view del package usano come contesto `STATE`, quindi nelle override hai già accesso ai dati principali:

- `$STATE['settings']`
- `$STATE['session']`
- `$STATE['authorization']`
- `$STATE['visible_events']`
- `$STATE['featured_event']`
- `$STATE['requires_invite_code']`
- `$STATE['max_participants']`
- `$STATE['allow_children']`
- `$STATE['max_children']`
- `$STATE['require_image_release']`

Le view RSVP vengono renderizzate dentro il layout frontend del progetto host, quindi ereditano automaticamente:

- `head.php`
- `header.php`
- `footer.php`
- asset e utility globali del sito

## Custom field e colonne response

Se il consumer registra custom field tramite l’estensione RSVP, ogni campo dichiarato viene materializzato anche come colonna dedicata nella tabella `rsvp_response`.

Regola di naming:

- campo `hotel` -> colonna `meta_hotel`
- campo `transfer_arrival` -> colonna `meta_transfer_arrival`

`metadata_json` resta disponibile solo come fallback per dati extra non dichiarati nello schema dell’estensione.

Dopo aver aggiunto o modificato i custom field dell’estensione, esegui l’update del progetto consumer per sincronizzare lo schema della tabella risposte.

## Multilingua

Il modulo è pronto per essere adattato al multilingua:

- route frontend traducibili via `lang/{locale}/urls.json`
- testi di default nel frontend già strutturati con fallback IT/EN nel codice
- le view override del consumer possono usare `__l()`, `__t()` e `__r()` come qualsiasi altra pagina del progetto

File URL inclusi:

- [lang/it/urls.json](/Users/andreamarinoni/Desktop/PROGETTI/template/rsvp/lang/it/urls.json)
- [lang/en/urls.json](/Users/andreamarinoni/Desktop/PROGETTI/template/rsvp/lang/en/urls.json)

## Payload RSVP supportato

Campi supportati direttamente:

- `participants`: array di oggetti `{ name, surname, dietary_requirements, is_child }`
- `contact_name`
- `contact_surname`
- `contact_phone`
- `contact_email`
- `event_key`
- `events`
- `notes`
- `accept_privacy_policy` + `privacy_policy_id`
- `accept_image_release` + `image_release_id`
- `invite_code_id`
- `source_url`

Campi legacy ancora normalizzati automaticamente:

- `name`, `surname`
- `children_name`, `children_surname`, `children_dietary_requirements`
- `partner_name`, `partner_surname`
- `phone`, `cel`
- `email`
- `event`
- `allergies`
- `requests`, `request`
- `lang`
- `photo_privacy`
- `password_id`
- `form` JSON legacy
- `request_url`

I campi extra non riconosciuti e non dichiarati nell’estensione finiscono in `metadata_json`.

I metadati extra non generano colonne dedicate nella tabella risposte:

- restano nel campo unico `metadata_json`
- nel backend dettaglio vengono mostrati come blocco compatto
- negli export finiscono in una sola colonna `Metadati`
