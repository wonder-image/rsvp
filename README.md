# wonder-image/rsvp

```bash
composer require wonder-image/rsvp:dev-main
```

Modulo RSVP per `wonder-image/app`, adattato al nuovo sistema moduli.

## Cosa include

- discovery automatica di `Model`, `Resource`, route e traduzioni via `module.json`
- backend resource-based per:
  - `rsvp/responses`
  - `rsvp/authorizations`
  - `rsvp/invite-codes`
  - `rsvp/invite-groups`
  - `rsvp/settings`
- endpoint API:
  - `api.rsvp.submit`
  - `api.rsvp.login`
  - `api.rsvp.logout`
  - `api.rsvp.session`
- route frontend:
  - `rsvp.home`
  - `rsvp.login`
- supporto RSVP libero o protetto da codice invito
- autorizzazioni RSVP separate dai gruppi invito per governare eventi visibili e limiti del form
- email di conferma ospite e admin
- registrazione del documento legale `image_release` dal modulo stesso
- contenuti frontend e catalogo eventi pronti per multilingua via JSON localizzati

## Installazione

1. Installa il package nel consumer.

2. Abilita il modulo in `custom/config/modules.php`.

```php
<?php

return [
    'rsvp' => [
        'enabled' => true,
        'config' => [],
    ],
];
```

Forma breve equivalente:

```php
<?php

return [
    'rsvp' => true,
];
```

3. Se ti serve configurazione file-based del modulo, usa `custom/config/modules/rsvp.php`.

4. Esegui l'update del progetto consumer per materializzare le tabelle dei model scoperti dal modulo.

## Endpoint e route

- `POST /api/rsvp/`
- `POST /api/rsvp/login/`
- `POST /api/rsvp/logout/`
- `GET /api/rsvp/session/`
- `GET /rsvp/`
- `GET /rsvp/login/`

Le route frontend sono traducibili via:

- [lang/it/urls.json](/Users/andreamarinoni/Desktop/PROGETTI/template/rsvp/lang/it/urls.json)
- [lang/en/urls.json](/Users/andreamarinoni/Desktop/PROGETTI/template/rsvp/lang/en/urls.json)

Le API restano intenzionalmente fisse e cross-lingua:

- `/api/rsvp/`
- `/api/rsvp/login/`
- `/api/rsvp/logout/`
- `/api/rsvp/session/`

## Frontend multilingua

La resource `rsvp/settings` espone due JSON chiave:

- `events_catalog_json`
- `page_content_json`

Entrambi accettano valori localizzati per lingua, per esempio:

```json
{
  "it": "Ti aspettiamo",
  "en": "We are waiting for you"
}
```

### Esempio `events_catalog_json`

```json
{
  "wedding": {
    "label": {
      "it": "Matrimonio",
      "en": "Wedding"
    },
    "date": "2026-09-14 18:30:00",
    "location_name": {
      "it": "Villa Rossi",
      "en": "Villa Rossi"
    },
    "location_address": {
      "it": "Via Roma 10, Milano",
      "en": "10 Via Roma, Milan"
    },
    "location_address_url": "https://maps.example.com/address",
    "location_position_url": "https://maps.example.com/position",
    "location_logo": "/upload/location/logo.png"
  },
  "aperitif": {
    "label": {
      "it": "Aperitivo",
      "en": "Aperitif"
    },
    "date": "2026-09-14 16:30:00"
  }
}
```

### Esempio `page_content_json`

```json
{
  "ambient_background": {
    "enabled": true,
    "video": "/upload/video/red-and-white-pulsating-disco-light-background.mp4"
  },
  "intro": {
    "enabled": true,
    "video": "/upload/video/disco-ball-background.mp4",
    "logo_path": "/upload/logo/site-logo.png"
  },
  "message_section": {
    "enabled": true,
    "content": {
      "it": "60 anni di storia sono 60 anni di voi. Festeggiamoci assieme.",
      "en": "60 years of history means 60 years of you. Let us celebrate together."
    }
  },
  "date_section": {
    "featured_event_key": "wedding",
    "eyebrow": {
      "it": "Ti aspettiamo",
      "en": "We are waiting for you"
    }
  },
  "location_section": {
    "eyebrow": {
      "it": "Location",
      "en": "Location"
    }
  },
  "countdown": {
    "enabled": true,
    "title": {
      "it": "Festeggeremo tra...",
      "en": "We will celebrate in..."
    }
  },
  "login": {
    "title": {
      "it": "Accesso RSVP",
      "en": "RSVP Access"
    },
    "text": {
      "it": "Inserisci il tuo codice invito per accedere alla pagina RSVP.",
      "en": "Enter your invite code to access the RSVP page."
    }
  },
  "form": {
    "headline": {
      "it": "Conferma la tua partecipazione",
      "en": "Confirm your attendance"
    },
    "submit_button": {
      "it": "Invia conferma",
      "en": "Send RSVP"
    }
  }
}
```

`page_content_json` puo essere definito a livello globale in `rsvp/settings` e sovrascritto per codice/autorizzazione via `rsvp/authorizations.page_content_json`, così codici diversi possono vedere varianti diverse della landing RSVP.

## Payload submission supportato

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
- `form` JSON incapsulato in stile legacy
- `request_url`

I campi extra non riconosciuti finiscono in `metadata_json`.

## Note

- il modulo non usa più `custom/build/table`, `custom/config/resource/resources.php` o route copiate nel consumer come meccanica primaria
- `config/module.php` registra il doc type `image_release` in fase bootstrap modulo
- le traduzioni del modulo vengono caricate automaticamente dal registry moduli
