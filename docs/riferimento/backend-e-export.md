# Backend ed export risposte

## Resource backend del modulo

Dopo l'attivazione, il backend espone queste resource (sezione menu **RSVP**):

| Path | Resource | Contenuto |
| --- | --- | --- |
| `rsvp/responses` | risposte | risposte ricevute (sola lettura per `rsvp_response_viewer`) |
| `rsvp/events` | eventi | eventi selezionabili |
| `rsvp/authorizations` | autorizzazioni | regole di visibilità/limiti |
| `rsvp/invite-codes` | codici invito | codici di accesso |
| `rsvp/invite-groups` | gruppi invito | segmentazione organizzativa |
| `rsvp/settings` | impostazioni | configurazione operativa (singleton) |

Per il gating dei permessi vedi [Permessi e ruoli](../configurazione/permessi.md).

## Dettaglio risposta

Ogni risposta mostra contatto, partecipanti, eventi selezionati, consensi,
custom field e — come blocco compatto — gli eventuali metadati extra non
dichiarati (`metadata_json`).

## Codice prenotazione

Ogni risposta ha un **codice prenotazione** leggibile:

* formato `pre_0000001`, salvato nel campo `booking_code`;
* per le risposte storiche senza valore, è calcolato come fallback dall'`id`.

Serve a raggruppare le righe della stessa prenotazione nell'export.

## Export

L'export usa il meccanismo di download integrato del framework, dichiarato
direttamente in `ResponseResource::tableLayoutSchema()` con
`->download(['xlsx', 'csv'])->downloadColumns([...])`. Il pulsante di export
compare quindi nella **pagina lista di default**, senza bisogno di una view
custom o di una route dedicata: la route `export` è registrata automaticamente
dal framework e usa gli stessi permessi di `list` (quindi anche
`rsvp_response_viewer` può esportare).

L'export genera **una riga per risposta** nei formati **XLSX** e **CSV**.

Colonne:

```text
Codice prenotazione, Creato il, [Conferma], Nome, Cognome, Email, Telefono,
Partecipanti, Bambini, Elenco partecipanti, Eventi selezionati, Codice invito,
Gruppo invito, Autorizzazione, Richieste, Privacy, Foto, Lingua, URL origine
```

Note:

* la colonna **Conferma** compare solo se `enable_attendance_status` è attivo;
* **Elenco partecipanti** elenca i nominativi (con suffisso `(bambino)`) ricavati
  da `participants_json`;
* i custom field dell'estensione sono aggiunti **in coda**, con la **label**
  dell'opzione configurata per i tipi `select`/`checkbox`/`radio`;
* l'export rispetta il filtro soft-delete e l'ordinamento di default della
  resource.

{% hint style="info" %}
Per cambiare colonne, formati o nome file dell'export, modifica
`->download(...)`, `->downloadColumns(...)` e `->downloadFileName(...)` in
`ResponseResource::tableLayoutSchema()`. Nessuna view o handler custom da toccare.
{% endhint %}
