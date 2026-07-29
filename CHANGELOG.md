# Changelog

## Unreleased

- **Accesso governato dall'autorizzazione.** Nuovo campo `Authorization.access`
  (`code` / `free`). Il form richiede SEMPRE un'autorizzazione attiva: quella
  del codice invito se presente, altrimenti l'unica autorizzazione "Libero"
  (accesso pubblico senza password). `context.php` risolve l'autorizzazione
  attiva e deriva `requires_invite_code` (serve un codice solo se non esiste una
  "Libero"); `Rsvp::canAccessForm` è gated sulla presenza dell'autorizzazione.
  Al più una autorizzazione può essere "Libero": salvandone una tale, le altre
  tornano a "con codice" (`afterStore`/`afterUpdate`). Il flag Impostazioni
  `require_invite_code` è ora superato da questo meccanismo.

- **Configurazione del form spostata da Impostazioni ad Autorizzazione.**
  `enable_attendance_status`, `require_image_release` e i mode 3-stati dei campi
  `field_age` / `field_sex` / `field_allergies` / `field_company`
  (Nascosto/Facoltativo/Obbligatorio) vivono ora su `Authorization` (insieme a
  capienze già presenti); rimossi da `Settings`. `context.php` risolve tutto
  dall'autorizzazione attiva (default del modulo se assente). Il `formSchema` e
  `view/components/form.php` mostrano/obbligano età/sesso/allergie/azienda in
  base al mode; la conferma partecipazione è **sempre disponibile nel backend**
  mentre il frontend la mostra solo se l'autorizzazione la abilita
  (`SubmissionNormalizer::fromPayload($payload, $attendanceEnabled)`).
- Email RSVP sdoppiate per esito confermato/rifiutato (cliente **e** admin),
  con oggetto/messaggio/invio distinti; nuovi default in `emails.json`.
- Nuovo flag Impostazioni "Verifica compilazione duplicata": rifiuta una
  submission con email o telefono già presenti su una risposta non cancellata.
- Nuovo campo evento **Data fine** (`ends_at`) opzionale, con placeholder
  `{{event_end_date}}` ecc. e colonna in tabella eventi.
- Fix (core `wonder-image/app`): normalizzazione simmetrica in lettura nel
  Model, così l'apostrofo non resta con lo slash di escape (`O\'Brien`).

- nuovi campi per ogni partecipante nel form frontend, raccolti in una card
  dedicata (`view/components/form.php`): **Età** (`number`, `->decimal(0)`,
  obbligatorio), **Sesso** (`select` a una colonna con placeholder e opzioni
  Uomo/Donna/Altro, obbligatorio). Le "allergie" riusano il campo esistente
  `dietary_requirements` ("Intolleranze o allergie", `textarea`). Età e sesso
  sono preservati nel JSON `participants`, mostrati nel dettaglio backend ed
  esportati (`export_participant_age`, `export_participant_sex` con etichetta
  leggibile via `rsvpSexText`).
- nuovo campo **Azienda** a livello di prenotazione (`company`, `->text()`,
  obbligatorio): colonna dedicata su `rsvp_response`, input vicino ai contatti,
  validazione server-side in `assertSubmission`, colonna d'export e riga nel
  dettaglio backend.
- con un solo partecipante possibile (`max adulti + max bambini == 1`) il form
  nasconde il select "Partecipanti" (reso come hidden `1`) e non mostra il
  titolo "Ospite 1" sopra la card.
- workaround al bug del widget select della lib (`selElmnt.value = this.id` in
  closure sull'ultimo select del documento): `reconcileSexSelects()` riallinea
  il valore nativo di ogni select sesso all'etichetta mostrata, prima dello
  snapshot di re-render, ad ogni interazione e prima del submit.
- l'export risposte XLSX/CSV genera ora una riga per partecipante, ripetendo i
  dati della prenotazione, del referente e dei custom field. Le risposte senza
  partecipanti conservano una riga di fallback, così i rifiuti restano visibili.

- i dati dell'evento in evidenza sono registrati come placeholder globali dei
  testi alla costruzione di `Rsvp::context()`: qualunque chiave lang può usare
  `{{event_name}}`, `{{event_date}}`, `{{event_location_name}}`, ecc. (elenco
  completo in `docs/riferimento/traduzioni.md`). Con nessun evento in evidenza
  i placeholder si risolvono in stringa vuota.

- **Fix**: il select partecipanti del form frontend ora arriva a
  `max adulti + max bambini` (prima si fermava a `max_participants`, che in
  backend è etichettato "Max adulti": con max adulti 2 e max bambini 2 il
  select mostrava 2 invece di 4).
- il form frontend verifica anche il numero di adulti compilati (partecipanti
  senza spunta bambino). Il feedback limiti è dichiarativo: resta visibile
  finché adulti o bambini superano i massimi e scompare appena la combinazione
  torna valida; finché la violazione persiste `participants_count` è marcato
  invalido via `setCustomValidity` e il bottone submit resta disabilitato.
  L'alert riporta entrambi i limiti
  (`pages.rsvp.form.children_max_text` con `{{max_adults}}`/`{{max_children}}`).
- il submit del form frontend usa il componente `Submit` (`.wi-submit`,
  disabled di default): il `check()` della lib lo abilita solo quando tutti i
  campi required sono compilati e ogni validity è valida. Nuova
  `setBlockDisabled()` nel form: disabilita davvero i campi dei blocchi
  nascosti (partecipa/non partecipa) e sospende il loro `required`, perché la
  `setDisabled()` della lib gestisce solo campi singoli e `check()` non salta
  i required disabilitati (i blocchi nascosti avrebbero bloccato il submit).
- validazione server-side dei limiti in `ResponseResource::assertSubmission`:
  nuove chiavi lang `pages.rsvp.api.submit.max_adults_exceeded` e
  `pages.rsvp.api.submit.max_children_exceeded`.

## 2.0.0

- **Fix**: `SubmissionNotifier::settings()` ora gestisce il caso in cui
  `rsvp_settings` non sia ancora stata creata sul sito consumer, evitando il
  fatal in bootstrap/primo accesso e ripiegando sui default finché non viene
  eseguito `php forge update --local`.

- testi lang separati secondo la convenzione `wonder-image/app`: eliminato
  `lang/{locale}/rsvp.json`; contenuto smistato in `pages.json`
  (`pages.rsvp.{home,login,form,countdown,event_date,location,common,api}`),
  `components.json` (`forms.fields.{field}.{label,options}`), `emails.json`
  (`summary.*`) e `legal.json` (`{doc}.label`). Aggiornati tutti i riferimenti
  `__t()`/`rsvp_trans()` nel codice (i route name `__r('rsvp.*')` restano).

- **BREAKING**: restructure completo della sorgente. Le classi di servizio
  (`FrontendContext`, `FrontendPage`, `InviteCodeSession`, `LegalDocumentSeeder`,
  `ResponseExporter`, `SubmissionNormalizer`, `SubmissionNotifier`) si spostano
  da `Wonder\Plugin\Rsvp\Support\*` a `Wonder\Plugin\Rsvp\Services\*`.
  `Support\` resta per registry e helper (`ExtensionRegistry`,
  `CustomFieldRegistry`, `CustomFieldRenderer`, `AbstractRsvpExtension`,
  `NullRsvpExtension`). Nessuna retro-compatibilità con la 1.x.
- `module.json` allineato al manifest documentato: versione `2.0.0`, path
  `tests` dichiarato. Le tabelle del modulo restano materializzate via
  `database.models` + `Model::tableSchema()` (meccanismo letto dal runtime).
- aggiunta suite smoke standalone in `tests/` (eseguibile con `php tests/smoke.php`).
- aggiunta documentazione GitBook in `docs/` per l'uso con `wonder-image/new-site`.
- export risposte spostato sul meccanismo `tableLayoutSchema()->download()` del
  framework (formati `xlsx`/`csv`, una riga per risposta). Rimossi la view lista
  custom (`views/backend/response/list.php`), la route/handler di export custom
  (`handlers/backend/response-export.php`) e il servizio `ResponseExporter`.
- **Fix**: migrazione `FormInput` → `FormField` in tutte le resource e nel
  sistema custom field. `Wonder\App\ResourceSchema\FormInput` non esiste nel
  framework 2.1: i form backend (Settings, Event, Authorization, InviteGroup,
  InviteCode) andavano in fatal al rendering.
- view backend `response/show.php` ricostruita con gli Element component
  (`Container`/`Card`/`SectionTitle`/`RichText`/`Link`) e `View::layout('backend.show', …)`.
- form RSVP frontend ri-architettato sulla rotta resource standard: la
  submission usa `api.resource.rsvp-responses.store` di `ResponseResource`
  (store-only; normalizzazione/validazione/notifiche in `mutateRequestValues()`
  /`afterStore()` leggendo il `$_POST`). Rimossi l'handler
  `handlers/api/frontend/submit.php` e la rotta `api.rsvp.submit`.
- `views/components/form.php` ripulito e ristilizzato: tutto dentro
  `.section > .content` con classi della lib, input con gli helper frontend
  (`text/phone/email/select/textarea/inputAcceptDocument`), `__t()` inline,
  escaping con `e()`, `id` solo dove serve al JS, submit via `formSubmit()`.
  `ResponseResource::formSchema()` torna al solo `attendance_status` (backend).
- viste frontend allineate alla lib `wonder-image/lib`: rimosse tutte le classi
  custom `rsvp-*` da `views/frontend/home.php`, `views/components/form.php`,
  `views/components/countdown.php`, `views/components/event-date.php` (ora solo
  classi utility/componenti della lib: `section`/`content`, `d-grid`/`col-*`,
  `title-big`/`subtitle`/`text`, `gap-*`, `tx-*`, ecc.). `rsvp-*` resta solo
  come `id`/`data-*` funzionali per il JS. Attendance via componente radio della
  lib (`checkbox(...,'radio')`), input dinamici inizializzati con `setInput()`,
  escaping con `e()`.
- rimossa la classe `Support\CustomFieldRenderer`: i custom field
  dell'estensione sono `FormField` e si renderizzano da soli (`$input->render()`).
  `ExtensionRegistry::inputs()` / `CustomFieldRegistry::visibleInputs()`
  forniscono i `FormField` del contesto (anche convertendo il formato array
  legacy).
- chiavi di traduzione `rsvp.frontend.*` rinominate in `rsvp.*` (rimosso il
  livello `frontend` da `lang/{it,en}/rsvp.json` e dai riferimenti). In
  `views/components/form.php` i testi usano `__t()` (errore se la traduzione
  manca) e l'escaping passa per `e()`.
- `Response` allineato alle convenzioni dei Model: `decorate()` per i valori
  derivati (`booking_code` risolto, `pretty_attendance_status`, `pretty_privacy`,
  `pretty_photo`) consumati da show backend ed export; `contact_email` su
  `->text()->lower()`. Schema della tabella invariato.

## 1.0.0

- prima versione del modulo RSVP adattata al sistema moduli di `wonder-image/app`
- discovery automatica di route, model, resource e traduzioni via `module.json`
- rimozione dell'integrazione primaria basata su copia in `custom/...`
