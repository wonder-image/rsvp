---
description: >-
  Modulo RSVP per wonder-image/app, pronto per i progetti basati su
  wonder-image/new-site.
---

# Wonder RSVP

`wonder-image/rsvp` è un **modulo** Composer per il framework
[`wonder-image/app`](https://github.com/wonder-image/app). Aggiunge a un sito
basato su [`wonder-image/new-site`](https://github.com/wonder-image/new-site) un
sistema RSVP completo:

* **frontend** con login a codice invito e form di conferma partecipazione;
* **backend** con resource per eventi, autorizzazioni, codici e gruppi invito,
  risposte e impostazioni;
* **API** stabili e cross-lingua per login, logout, sessione e invio risposta;
* un **sistema di estensione** per aggiungere custom field, hook di submit e
  override SEO senza toccare il package.

Il modulo viene scoperto e integrato automaticamente dal sistema moduli del
framework tramite `module.json`: niente copia di file dentro `custom/...`, niente
`repositories` custom se installi da Packagist.

## A chi è rivolta questa guida

Questa documentazione descrive **come usare il modulo dentro un sito**
`wonder-image/new-site`. Non è la documentazione interna del framework: per il
contratto dei moduli e l'API di `Model`/`Resource` fai riferimento alla
documentazione di `wonder-image/app`.

## Mappa rapida

| Voglio… | Vai a |
| --- | --- |
| Installare e abilitare il modulo | [Installazione](getting-started/installazione.md) |
| Capire com'è organizzato il codice | [Struttura del modulo](getting-started/struttura-modulo.md) |
| Configurare opzioni, eventi e inviti | [Configurazione](configurazione/impostazioni.md) |
| Personalizzare le pagine frontend | [Personalizzare le view](frontend/personalizzare-le-view.md) |
| Aggiungere campi personalizzati | [Custom field](estensioni/custom-field.md) |
| Integrare le API | [Riferimento API](riferimento/api.md) |

## Requisiti

* PHP `^8.2`
* `wonder-image/app` `^2.1`
* un sito scaffoldato da `wonder-image/new-site`
