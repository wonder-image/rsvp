# Struttura del modulo

Il modulo segue la struttura consigliata per i moduli `wonder-image/app`. Tutto
il codice PHP vive sotto il namespace base `Wonder\Plugin\Rsvp\` (PSR-4 → `src/`).

```text
wonder-image/rsvp/
├── module.json              # manifest del modulo (contratto tecnico)
├── composer.json            # package Composer + autoload PSR-4
├── src/
│   ├── Rsvp.php             # entrypoint (implementa ModuleInterface)
│   ├── helpers.php          # funzioni globali rsvp* (autoload "files")
│   ├── Models/             # Model + tableSchema/dataSchema
│   ├── Resources/          # Resource backend/API CRUD
│   ├── Services/           # logica applicativa (operazioni)
│   ├── Support/            # registry e helper di estensione
│   └── Contracts/         # interfacce pubbliche di estensione
├── config/
│   ├── module.php          # config di default + boot del modulo
│   ├── permissions.php     # permessi backend registrati dal modulo
│   └── routes/            # route frontend / backend / api
├── handlers/              # handler delle route (frontend/backend/api)
├── views/                 # view frontend + componenti + view backend
├── lang/                  # traduzioni it/en (pages, components, emails, legal, urls)
├── resources/assets/      # asset statici del modulo
└── tests/                 # smoke test standalone
```

## `src/Services/` vs `src/Support/`

A partire dalla versione 2.0.0 la sorgente è separata in due ruoli netti.

**`Services/`** — classi che **eseguono operazioni** (logica di dominio,
orchestrazione, side-effect):

| Classe | Ruolo |
| --- | --- |
| `FrontendContext` | costruisce lo stato (`$STATE`) della pagina RSVP |
| `FrontendPage` | renderizza la view dentro il layout frontend del sito |
| `InviteCodeSession` | gestisce login/logout e sessione del codice invito |
| `SubmissionNormalizer` | normalizza il payload del form in una row `rsvp_response` |
| `SubmissionNotifier` | invia le email ad admin e ospite |
| `LegalDocumentSeeder` | crea il documento legale "liberatoria immagini" |

**`Support/`** — registry, helper e classi base **usate** dai servizi e dal sito:

| Classe | Ruolo |
| --- | --- |
| `ExtensionRegistry` | risolve l'estensione RSVP dichiarata dal sito |
| `CustomFieldRegistry` | normalizza i custom field e ne fornisce i `FormField` per il render |
| `AbstractRsvpExtension` | classe base da estendere nel sito |
| `NullRsvpExtension` | estensione vuota di default |

{% hint style="info" %}
**Migrazione dalla 1.x.** Le classi di servizio si sono spostate da
`Wonder\Plugin\Rsvp\Support\*` a `Wonder\Plugin\Rsvp\Services\*`. Se nel sito
referenziavi direttamente, ad esempio, `Support\SubmissionNotifier`, aggiorna
l'import a `Services\SubmissionNotifier`. Le classi di `Support/` elencate sopra
mantengono il namespace.
{% endhint %}

## Entrypoint `Rsvp`

`Wonder\Plugin\Rsvp\Rsvp` implementa `Wonder\App\Module\Contracts\ModuleInterface`
ed espone i path del modulo (`handlerPath()`, `viewPath()`, `langPath()`,
`assetPath()`). Aggiunge due helper utili nelle view del sito:

* `Rsvp::component(string $name, array $args = [])` — include un componente
  riutilizzabile da `views/components/` (override dal sito in
  `custom/modules/rsvp/views/components/`).
* `Rsvp::renderPage(array $config)` — scaffold per pagine RSVP-gated aggiuntive
  del sito (gestisce stato, redirect al login, hook SEO e layout).

Vedi [Personalizzare le view](../frontend/personalizzare-le-view.md) per gli esempi.
