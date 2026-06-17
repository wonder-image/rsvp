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

## Sovrascrivere o aggiungere testi dal sito

Le view override del sito possono usare `__l()`, `__t()` e `__r()` come qualsiasi
altra pagina. Per personalizzare le stringhe del modulo, aggiungi le chiavi
corrispondenti nei file lingua del sito (`lang/{locale}/*.json`): le traduzioni
del sito hanno priorità su quelle del package.

## Aggiungere una lingua

Per supportare una nuova lingua, fornisci i file `pages.json`, `components.json`,
`emails.json`, `legal.json` e `urls.json` per quel locale nel sito (o
contribuiscili al package). I testi senza traduzione ricadono sui fallback
hardcoded del modulo.
