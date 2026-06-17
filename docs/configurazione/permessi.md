# Permessi e ruoli

Il modulo registra i propri permessi tramite `config/permissions.php`, che il
core fonde nel registry dei permessi del sito dopo il file base del framework.

## Permesso custom: `rsvp_response_viewer`

È un permesso backend pensato per dare accesso in **sola lettura** alle risposte
RSVP, senza esporre la configurazione del modulo.

Un utente con `rsvp_response_viewer` **può**:

* vedere la voce backend `rsvp/responses`;
* aprire la lista delle risposte;
* aprire il dettaglio di una risposta;
* esportare le risposte (CSV/XLS).

Un utente con `rsvp_response_viewer` **non può**:

* modificare le impostazioni RSVP;
* gestire eventi, autorizzazioni, codici o gruppi invito;
* modificare lo stato di una risposta;
* cancellare risposte.

## Gating delle resource

Il gating è dichiarato in `Resource::permissionSchema()` di ogni resource del
modulo. In sintesi:

| Resource | list / view | create / edit / update / delete |
| --- | --- | --- |
| `rsvp/events` | `admin` | `admin` |
| `rsvp/authorizations` | `admin` | `admin` |
| `rsvp/invite-codes` | `admin` | `admin` |
| `rsvp/invite-groups` | `admin` | `admin` |
| `rsvp/settings` | `admin` | `admin` |
| `rsvp/responses` | `admin`, `rsvp_response_viewer` | edit/update: `admin` · delete: `admin` |

La gestione completa del dominio RSVP resta quindi riservata ad `admin`, mentre
`rsvp_response_viewer` è un ruolo di sola consultazione delle risposte.

## Assegnare il permesso a un ruolo del sito

Il permesso `rsvp_response_viewer` è disponibile non appena il modulo è
abilitato. Per estendere o sovrascrivere il registry dei permessi lato sito usa
`custom/config/permissions.php`, che viene applicato dopo il file base del
framework e dopo il merge dei permessi dei moduli.
