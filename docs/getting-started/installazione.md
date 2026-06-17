# Installazione

## 1. Installa il package

Dal root del sito (`wonder-image/new-site`):

```bash
composer require wonder-image/rsvp
```

Il modulo dipende da `wonder-image/app`: Composer lo installa sotto
`vendor/wonder-image/rsvp` e il sistema moduli del framework lo scopre
automaticamente leggendo `module.json` (via `vendor/composer/installed.php`, con
fallback sulla scansione di `vendor/wonder-image/*/module.json`).

{% hint style="info" %}
Non servono `repositories` custom nel `composer.json` del sito se il package è
pubblicato su Packagist. Non copiare mai file del modulo dentro `custom/...`:
l'integrazione è automatica.
{% endhint %}

## 2. Abilita il modulo

I moduli sono **scoperti** in automatico ma **attivati** esplicitamente dal sito.
Dichiara il modulo in `custom/config/modules.php`:

```php
<?php

return [
    'rsvp' => true,
];
```

Forma estesa, con configurazione ed estensione (vedi [Custom field](../estensioni/custom-field.md)):

```php
<?php

return [
    'rsvp' => [
        'enabled' => true,
        'config' => [
            'extension' => \App\Rsvp\WeddingExtension::class,
        ],
    ],
];
```

Regole del sistema moduli:

* se un modulo non è dichiarato, non viene abilitato;
* se è abilitato ma non scoperto, il bootstrap fallisce con un errore esplicito;
* se dipende da un altro modulo non abilitato, il bootstrap fallisce.

## 3. Materializza le tabelle

Le tabelle del modulo (`rsvp_event`, `rsvp_authorization`, `rsvp_invite_code`,
`rsvp_invite_group`, `rsvp_response`, `rsvp_settings`) sono definite dai `Model`
del modulo tramite `Model::tableSchema()` e create dall'`UpdateRunner` del
framework durante l'update del sito:

```bash
php forge update --local
```

{% hint style="warning" %}
Lo schema delle tabelle del modulo è generato **solo** da `database.models`
(dichiarato in `module.json`) + `Model::tableSchema()`. Esegui di nuovo
`php forge update --local` ogni volta che aggiungi o modifichi custom field
dell'estensione, così le colonne `meta_*` su `rsvp_response` vengono create.
{% endhint %}

## 4. Verifica l'attivazione

Dal root del sito:

```bash
php forge status:modules
php forge validate:module rsvp
```

`status:modules` elenca i moduli scoperti e il loro stato; `validate:module`
verifica manifest e contratto del modulo.

## Disinstallazione

Per disattivare il modulo imposta `'rsvp' => false` in
`custom/config/modules.php`. Le route, le resource e le traduzioni del modulo
spariscono dal runtime. Le tabelle dati restano in database: rimuovile a mano se
non ti servono più.
