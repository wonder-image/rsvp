# Traduzioni e URL

Il modulo è multilingua e usa `__t()` per i testi frontend di default, i
messaggi email di default e i messaggi API mostrati all'utente.

## File lingua inclusi

I file vivono in `lang/{locale}/` dentro il package e sono scoperti
automaticamente dal sistema moduli. Il modulo segue la separazione dei testi di
`wonder-image/app`:

| File | Contenuto | Namespace |
| --- | --- | --- |
| `lang/{locale}/pages.json` | contenuti di pagina/UI RSVP (home, login, form, countdown, ecc., messaggi API) | `pages.rsvp.*` |
| `lang/{locale}/components.json` | label e opzioni dei campi del form | `components.forms.fields.*` |
| `lang/{locale}/emails.json` | testi email + riepilogo | `emails.*` |
| `lang/{locale}/legal.json` | label dei tipi di documento legale | `legal.{doc}.label` |
| `lang/{locale}/urls.json` | slug tradotti delle route frontend | — |

Lingue incluse di default: `it`, `en`.

## URL tradotti

Gli slug delle route frontend sono traducibili via `urls.json`. Esempio
(`lang/it/urls.json`):

```json
{
    "rsvp": "rsvp",
    "rsvp/login": "rsvp/accedi"
}
```

Risultato: in italiano la pagina di login è servita su `/rsvp/accedi/`, in
inglese su `/rsvp/login/`. Il route name (`rsvp.login`) resta invariato: per
questo nei template usi sempre `__r('rsvp.login')` e non un path hardcoded.

## Placeholder dell'evento in evidenza

Quando `Rsvp::context()` viene costruito, i dati dell'evento in evidenza
vengono registrati come variabili globali dei testi: qualunque chiave lang (del
modulo o del sito) può usarli con la sintassi `{{placeholder}}`, senza passare
`$replacements` a `__t()`.

| Placeholder | Contenuto |
| --- | --- |
| `{{event_key}}` | codice evento |
| `{{event_name}}` | nome evento |
| `{{event_description}}` | descrizione evento |
| `{{event_date}}` | data leggibile (es. "2 giugno 2026") |
| `{{event_hour}}` | ora di inizio (`H:i`) |
| `{{event_day}}` | giorno numerico (`d`) |
| `{{event_pretty_day}}` | giorno della settimana leggibile |
| `{{event_month}}` | mese numerico (`m`) |
| `{{event_pretty_month}}` | mese leggibile |
| `{{event_location_name}}` | nome della location |
| `{{event_location_address}}` | indirizzo della location |
| `{{event_location_address_url}}` | URL indirizzo (mappe) |
| `{{event_location_position_url}}` | URL posizione (parcheggi/ingresso) |

Esempio (`lang/it/pages.json` del sito):

```json
{
    "rsvp": {
        "home": {
            "seo": {
                "title": "{{society_name}} - {{event_date}}",
                "description": "Ti aspettiamo il {{event_date}} presso {{event_location_name}}."
            }
        }
    }
}
```

Con nessun evento in evidenza i placeholder si risolvono in stringa vuota. Se
la sessione (codice invito) cambia gli eventi visibili, i placeholder seguono
l'evento in evidenza di quella sessione.

## Sovrascrivere o aggiungere testi dal sito

Le view override del sito possono usare `__l()`, `__t()` e `__r()` come qualsiasi
altra pagina. Per personalizzare le stringhe del modulo, aggiungi le chiavi
corrispondenti nei file lingua del sito (`lang/{locale}/*.json`): le traduzioni
del sito hanno priorità su quelle del package.

> La priorità del sito sui moduli richiede `wonder-image/app` con l'ordine di
> caricamento core → moduli → sito (fix in `TranslationBootstrap` +
> `app/service/lang.php`). Con versioni precedenti del framework i testi dei
> moduli sovrascrivono quelli del sito.

## Aggiungere una lingua

Per supportare una nuova lingua, fornisci i file `pages.json`, `components.json`,
`emails.json`, `legal.json` e `urls.json` per quel locale nel sito (o
contribuiscili al package). I testi senza traduzione ricadono sui fallback
hardcoded del modulo.
