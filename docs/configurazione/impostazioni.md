# Impostazioni RSVP

Dopo l'attivazione, il backend espone la resource singleton **`rsvp/settings`**
(voce di menu *RSVP → Impostazioni*). Contiene la sola configurazione operativa
del modulo.

## Campi disponibili

| Campo | Tipo | Descrizione |
| --- | --- | --- |
| `require_invite_code` | bool | se `true`, l'accesso al form richiede un codice invito valido |
| `login_title` | testo | titolo della pagina di login (override del default tradotto) |
| `login_text` | testo | testo introduttivo della pagina di login |
| `enable_attendance_status` | bool | abilita la scelta esplicita "Confermato / Declinato" |
| `max_participants` | numero | numero massimo di adulti per risposta |
| `allow_children` | bool | consente di indicare bambini |
| `max_children` | numero | numero massimo di bambini per risposta |
| `require_image_release` | bool | richiede l'accettazione della liberatoria immagini |
| `admin_email` | email | destinatario delle notifiche admin (fallback: email del sito) |
| `admin_notifications` | bool | invio email all'admin a ogni risposta |
| `customer_notifications` | bool | invio email di conferma all'ospite |
| `customer_subject` / `customer_message` | testo | override del template email ospite |
| `admin_subject` / `admin_message` | testo | override del template email admin |

{% hint style="info" %}
I limiti (`max_participants`, `allow_children`, `max_children`) sono **default**.
Una `rsvp_authorization` collegata al codice invito può sovrascriverli per
specifici gruppi di ospiti — vedi [Dominio RSVP](dominio-rsvp.md).
{% endhint %}

## Cosa NON sta nelle impostazioni

La configurazione strutturale dei contenuti non vive in `rsvp/settings`:

* gli **eventi** stanno nella resource `rsvp/events`;
* la **personalizzazione del frontend** (HTML, sezioni, stile) si fa nelle
  [view override](../frontend/personalizzare-le-view.md) del sito.

## Template email e placeholder

Se i campi `*_subject` / `*_message` sono vuoti, il modulo usa i default
tradotti in `lang/{locale}/emails.json`. Nei messaggi puoi usare i placeholder:

* `{{contact_name}}`, `{{contact_surname}}`
* `{{event_name}}`, `{{event_starts_at}}`
* `{{summary_html}}` — riepilogo HTML della risposta
* `{{response_url}}` — link al dettaglio backend della risposta

## Liberatoria immagini

All'attivazione, il modulo registra il tipo di documento legale `image_release`
e, tramite `LegalDocumentSeeder`, crea una versione iniziale della liberatoria
per ogni lingua del sito (se la tabella dei documenti legali esiste già). Con
`require_image_release = true`, il consenso compare nel form ed è obbligatorio.
