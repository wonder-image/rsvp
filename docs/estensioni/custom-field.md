# Custom field

Il sito può aggiungere **campi personalizzati** al form RSVP dichiarando una
classe di estensione. Ogni campo dichiarato viene renderizzato nel form e
materializzato come colonna dedicata sulla tabella `rsvp_response`.

## 1. Crea l'estensione

Crea una classe nel sito (es. `app/Rsvp/WeddingExtension.php`) che estende
`AbstractRsvpExtension` e dichiara i campi come `FormField`:

```php
<?php

namespace App\Rsvp;

use Wonder\App\ResourceSchema\FormField;
use Wonder\Plugin\Rsvp\Support\AbstractRsvpExtension;

final class WeddingExtension extends AbstractRsvpExtension
{
    public function formInputs(): array
    {
        return [
            FormField::key('society_name')
                ->text()
                ->label('Nome attività (se sei un ristoratore o enotecario)'),

            FormField::key('bus')
                ->radio([
                    'no'            => 'No, non uso il servizio bus',
                    'famagosta'     => 'Famagosta - 16:45',
                    'porta-venezia' => 'Porta Venezia - 17:00',
                ])
                ->label('Vuoi usare il servizio bus privato?')
                ->required()
                ->value('no'),
        ];
    }

    public function allFormInputs(): array
    {
        return [...$this->formInputs()];
    }
}
```

* `formInputs()` — campi visibili nel **contesto corrente** (possono dipendere
  da sessione, codice invito, lingua…).
* `allFormInputs()` — **super-set** di tutti i campi possibili. Lo schema sync lo
  usa per creare tutte le colonne `meta_*`: se `formInputs()` è session-aware,
  qui devi sempre restituire l'unione completa, altrimenti in fase di update
  (senza sessione) nessuna colonna verrebbe creata.

## 2. Registra l'estensione

In `custom/config/modules.php`:

```php
<?php

return [
    'rsvp' => [
        'enabled' => true,
        'config' => [
            'extension' => \App\Rsvp\WeddingExtension::class,
        ],
    ],
];
```

## 3. Sincronizza lo schema

Dopo aver aggiunto o modificato i campi, esegui l'update del sito per creare le
colonne sulla tabella risposte:

```bash
php forge update --local
```

## Naming delle colonne

Ogni campo è materializzato come colonna `meta_<key>` (chiave normalizzata in
lowercase/underscore):

| Campo | Colonna |
| --- | --- |
| `hotel` | `meta_hotel` |
| `transfer_arrival` | `meta_transfer_arrival` |

I valori dei custom field sono risolti **prima** della persistenza e le colonne
`meta_*` sono preservate anche se l'estensione usa `beforeSubmit()`: l'hook non
deve ricostruirle a mano. `metadata_json` resta solo come fallback per dati extra
non dichiarati nello schema.

I custom field `select`, `checkbox` e `radio` sono gestiti in modo coerente in
tutto il modulo: rendering nel form, persistenza su `meta_<key>`, e in backend /
export / email summary vengono mostrate le **label** delle opzioni, non i valori
tecnici.

## Tipi di campo supportati

`text`, `email`, `phone`/`tel`, `number`, `url`, `textarea`, `select`,
`checkbox`, `radio`. I tipi non riconosciuti ricadono su `text`.

## Formato array (alternativa)

In alternativa ai `FormField`, l'estensione può dichiarare i campi come array
implementando `fields()` / `allFields()`:

```php
public function fields(): array
{
    return [
        'society_name' => [
            'type'     => 'text',
            'label'    => 'Nome attività',
            'required' => true,
        ],
    ];
}
```

Il formato `FormField` è quello consigliato: evita di costruire manualmente gli
array di schema e riusa lo stesso builder del resto del framework.
