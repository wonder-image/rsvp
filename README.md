# wonder-image/rsvp

Modulo RSVP per [`wonder-image/app`](https://github.com/wonder-image/app),
pronto per i siti basati su
[`wonder-image/new-site`](https://github.com/wonder-image/new-site).

Aggiunge un sistema RSVP completo: frontend con login a codice invito e form di
conferma, backend con resource per eventi/autorizzazioni/inviti/risposte/
impostazioni, API stabili e un sistema di estensione per custom field, hook di
submit e override SEO.

## Documentazione

La guida completa all'uso del modulo è in [`docs/`](docs/README.md) (GitBook):

* [Installazione](docs/getting-started/installazione.md)
* [Struttura del modulo](docs/getting-started/struttura-modulo.md)
* [Configurazione](docs/configurazione/impostazioni.md)
* [Frontend e personalizzazione delle view](docs/frontend/route-e-flusso.md)
* [Custom field e hook](docs/estensioni/custom-field.md)
* [Riferimento API](docs/riferimento/api.md)

## Quick start

```bash
composer require wonder-image/rsvp
```

Abilita il modulo nel sito (`custom/config/modules.php`):

```php
<?php

return [
    'rsvp' => true,
];
```

Materializza le tabelle e avvia:

```bash
php forge update --local
php forge start
```

## Requisiti

* PHP `^8.2`
* `wonder-image/app` `^2.1`

## Sviluppo

```bash
composer install
php tests/smoke.php
```

Vedi [Sviluppo e test](docs/riferimento/sviluppo-e-test.md). Changelog in
[CHANGELOG.md](CHANGELOG.md).

## Licenza

MIT.
