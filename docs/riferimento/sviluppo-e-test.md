# Sviluppo e test

Questa pagina riguarda chi **sviluppa il modulo** (non chi lo usa nel sito).

## Setup locale

Il modulo è un package Composer che dipende da `wonder-image/app`. Per lavorarci
in isolamento:

```bash
composer install
composer dump-autoload
```

Per provarlo dentro un sito reale, installalo nel sito (via Packagist o un
repository `path` locale) e abilitalo in `custom/config/modules.php`, poi:

```bash
php forge update --local
php forge start
```

## Validazione

Dopo ogni modifica al codice o al manifest:

```bash
# lint di ogni file PHP toccato
php -l src/.../File.php

# rigenera l'autoload quando aggiungi/sposti/rinomini classi PSR-4
composer dump-autoload

# valida il manifest e il contratto del modulo (dal root di un sito)
php forge validate:module rsvp
php forge status:modules
```

## Smoke test

Il modulo include una suite smoke standalone (senza PHPUnit né database) che
verifica la logica pura di helper e normalizzazione:

```bash
php tests/smoke.php
```

Esce con codice `0` se tutte le asserzioni passano, `1` al primo fallimento — è
integrabile in CI. Aggiungi qui nuove asserzioni quando estendi gli helper o la
normalizzazione del payload.

## Materializzazione delle tabelle

Le tabelle del modulo sono definite **solo** dai `Model` (`Model::tableSchema()`)
e dichiarate in `module.json` tramite `database.models`. L'`UpdateRunner` del
framework le crea iterando i model registrati durante `php forge update --local`.

{% hint style="warning" %}
Il runtime corrente **non** consuma `build/update`, `build/row`, `install.php` o
`uninstall.php` a livello di modulo (sono previsti nel contratto ma non ancora
implementati nel core). Non aggiungere scaffolding `build/` per il modulo: la
sorgente di verità dello schema è `tableSchema()`.
{% endhint %}

## Convenzioni di codice

* namespace base `Wonder\Plugin\Rsvp\` (PSR-4 → `src/`);
* operazioni in `src/Services/`, registry/helper in `src/Support/`,
  interfacce pubbliche in `src/Contracts/`;
* gli input dei form passano sempre da `FormField` (mai HTML grezzo);
* niente path del sito hardcoded dentro il package.
