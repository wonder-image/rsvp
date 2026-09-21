# Futures

Idee e funzionalità pianificate ma **non ancora implementate**. Ogni voce è una
proposta da rifinire (brainstorming + design) prima di passare al codice: qui si
annota il *cosa* e il *perché*, non l'implementazione definitiva.

---

## Chiusura iscrizioni per evento (data limite di compilazione)

**Obiettivo.** Poter impostare, per ogni evento, una data oltre la quale il
modulo RSVP non è più compilabile per quell'evento ("chiusura iscrizioni").
Quando **tutti** gli eventi disponibili hanno le iscrizioni chiuse, il frontend
deve mostrare un messaggio di invito scaduto con un contatto per informazioni.

**Comportamento atteso.**

- Nuovo campo data sull'evento (es. `registration_closes_at`) nel model
  [`Event`](src/Models/Event.php). Il campo entra nello `tableSchema()` /
  `dataSchema()` come le altre date (`starts_at` / `ends_at`).
- Con data valorizzata: **oltre** quella data l'evento non è più selezionabile /
  compilabile nel form frontend (l'evento resta comunque visibile per contesto,
  ma la submission per quell'evento è rifiutata anche lato validazione, non solo
  UI).
- Campo vuoto = nessuna scadenza (iscrizioni sempre aperte, comportamento
  attuale).
- Se **tutti** gli eventi disponibili risultano chiusi (o non ce n'è nessuno
  aperto), il form mostra al posto dei campi:
  - titolo/messaggio **"Invito scaduto"**;
  - riga **"Per ulteriori informazioni contattare {{email}}"**, con `{{email}}`
    risolto dal contatto configurato.

**Punti da definire prima di implementare.**

- **Sorgente di `{{email}}`**: contatto da `Settings` (email pubblica del
  modulo) oppure email dell'autorizzazione/evento? Da decidere e documentare.
- **Interazione con `active`**: la chiusura iscrizioni è distinta dal flag
  `active` dell'evento (un evento può essere attivo/visibile ma con iscrizioni
  chiuse). Chiarire se un evento `active = false` conta comunque come "chiuso".
- **Confine temporale**: la data è inclusiva o esclusiva? A che ora scatta la
  chiusura (fine giornata locale della data indicata?).
- **Validazione backend**: aggiungere il controllo nel percorso di submission
  ([`ResponseResource`], `mutateRequestValues()` / `assertSubmission`) così che
  una POST fuori termine restituisca 422, non solo un blocco lato UI.
- **Traduzioni**: nuove chiavi `rsvp.*` per "Invito scaduto" e per la riga di
  contatto (con placeholder email) in `lang/it/rsvp.json` e `lang/en/rsvp.json`.
- **Backend/form**: nuovo input data nel form evento (resource di `Event`),
  coerente con lo stile lib/`FormField` del modulo.
