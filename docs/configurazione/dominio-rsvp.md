# Dominio RSVP: eventi, autorizzazioni, inviti

Il dominio RSVP è composto da quattro resource che lavorano insieme. Tutte sono
raggruppate nel menu backend sotto la sezione **RSVP**.

## `rsvp_event` — eventi

Definisce un evento selezionabile nel form RSVP (matrimonio, aperitivo, cena,
festa aziendale, welcome party…).

Campi principali: `code`, `name`, `starts_at`, `location_name`,
`location_address`, `location_address_url`, `location_position_url`,
`location_logo`, `position`, `active`.

Il `code` è la chiave logica usata ovunque (`event_key`) per collegare risposte,
autorizzazioni e contenuti.

## `rsvp_authorization` — autorizzazioni

Definisce **cosa un invitato può vedere o selezionare**. È il punto in cui
applichi regole diverse a gruppi di ospiti diversi.

Campi principali:

* `visible_event_keys_json` — elenco dei `code` evento visibili (es.
  `["WEDDING", "APERITIF"]`). Se vuoto, sono visibili tutti gli eventi attivi.
* `max_participants`, `allow_children`, `max_children` — limiti che
  **sovrascrivono** i default di `rsvp/settings`.

Esempi: `WEDDING_ONLY`, `WEDDING_AND_APERITIF`, `CORPORATE_GUEST`.

## `rsvp_invite_code` — codici invito

È il codice che l'utente inserisce nella pagina di login.

Campi principali:

* `code` — il codice digitato (case-insensitive);
* `usage_mode` — `single_use` (monouso) o `multiple_use` (multiuso);
* `authorization_id` — da cui eredita le regole di visibilità e i limiti;
* `invite_group_id` — gruppo organizzativo opzionale;
* `active`.

Il codice apre la sessione RSVP, eredita le regole dalla sua `authorization` e
può essere monouso o multiuso.

## `rsvp_invite_group` — gruppi invito

Contenitore **organizzativo** (famiglia, colleghi, fornitori, tavoli, lato
sposo/sposa). Non governa il frontend da solo: le regole di visibilità restano
nella `authorization`. Serve per segmentare e filtrare in backend.

## Come si incastrano

```text
invite_code ──► authorization ──► visible_event_keys_json ──► events
     │
     └──► invite_group (segmentazione organizzativa)
```

## Flusso consigliato di configurazione

1. configura `rsvp/settings`;
2. crea gli eventi in `rsvp/events`;
3. crea una o più `rsvp/authorizations`;
4. crea eventuali `rsvp/invite-groups`;
5. crea i `rsvp/invite-codes`;
6. condividi `rsvp.login` o `rsvp.home` a seconda del caso.

### Esempio

* evento `WEDDING`, evento `APERITIF`;
* autorizzazione `WEDDING_ONLY` con `visible_event_keys_json = ["WEDDING"]`;
* autorizzazione `WEDDING_AND_APERITIF` con
  `visible_event_keys_json = ["WEDDING", "APERITIF"]`;
* gruppo `FAMIGLIA_ROSSI`;
* codice `ROSSI01` collegato a `FAMIGLIA_ROSSI` e `WEDDING_AND_APERITIF`.
