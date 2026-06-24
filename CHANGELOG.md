# Changelog

## Unreleased

### Changed (BREAKING)
- Rimosso `Rsvp::component()`. I componenti del modulo si renderizzano ora con `View::component('rsvp/<nome>')`.
- `Rsvp::viewPath()` risolve ora le view con la catena di override `custom/view/{area}/rsvp/...` (in precedenza `custom/modules/rsvp/view/...`). Sposta gli override esistenti al nuovo percorso.

### Added
- Slot nel form RSVP: `before_fields`, `after_fields`, `before_submit`, `extra_cta`.
- `php forge module:publish rsvp` per scaffoldare gli override delle view nel sito.

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
