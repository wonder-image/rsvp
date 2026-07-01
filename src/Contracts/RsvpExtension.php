<?php

namespace Wonder\Plugin\Rsvp\Contracts;

/**
 * Punto di estensione del modulo RSVP per il consumer.
 *
 * Il consumer dichiara una classe che implementa questa interface (o estende
 * Wonder\Plugin\Rsvp\Support\AbstractRsvpExtension) e la registra in
 * `custom/config/modules.php`:
 *
 *     'rsvp' => [
 *         'enabled' => true,
 *         'config' => [
 *             'extension' => App\Rsvp\WeddingExtension::class,
 *         ],
 *     ],
 *
 * I custom field vengono materializzati in colonne dedicate della tabella
 * `rsvp_response` con prefisso `meta_`. Dopo aver aggiunto o modificato i
 * campi dell'estensione serve aggiornare lo schema del consumer.
 *
 * Per dichiarare i custom field come `FormField` usa il contratto opzionale
 * `Wonder\Plugin\Rsvp\Contracts\SupportsFormInputs` oppure estendi
 * `Wonder\Plugin\Rsvp\Support\AbstractRsvpExtension` e override
 * `formInputs()` / `allFormInputs()`.
 */
interface RsvpExtension
{
    /**
     * Custom field da renderizzare nel form RSVP per la SESSIONE corrente.
     *
     * Può ritornare un sottoinsieme di allFields() in base alla logica del
     * consumer (es. solo per certi codici invito).
     *
     * Formato:
     *     [
     *         'hotel' => [
     *             'type'        => 'select',  // text|email|phone|number|textarea|select|checkbox
     *             'label'       => 'Hotel',
     *             'options'     => ['marriott' => 'Marriott', 'hilton' => 'Hilton'], // select|checkbox
     *             'required'    => true,
     *             'value'       => '',        // default opzionale
     *         ],
     *     ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function fields(): array;

    /**
     * SUPER-SET di TUTTI i custom field possibili che l'estensione può
     * mai dichiarare, indipendentemente da sessione, ruolo, ecc. È
     * letto dallo schema sync (UpdateRunner) per creare le colonne
     * `meta_<key>` sulla tabella `rsvp_response`.
     *
     * Senza questo metodo, una fields() session-aware risulterebbe
     * vuota in fase di deploy (niente sessione), nessuna colonna verrebbe
     * creata, e l'INSERT a runtime farebbe `Unknown column meta_<key>`.
     *
     * Default in AbstractRsvpExtension: ritorna $this->fields() (per
     * compatibilità con estensioni che non hanno gating per sessione).
     * Override se fields() è session-aware.
     *
     * @return array<string, array<string, mixed>>
     */
    public function allFields(): array;

    /**
     * Mutator del payload prima della persistenza. Tornare l'array
     * modificato (o invariato). Lanciare un'eccezione per abortire.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function beforeSubmit(array $payload): array;

    /**
     * Hook dopo la persistenza della response. Utile per side-effect
     * (notifiche aggiuntive, integrazioni esterne, ecc.).
     *
     * @param array<string, mixed> $response  Row Response appena salvata
     * @param array<string, mixed> $payload   Payload originale
     */
    public function afterSubmit(array $response, array $payload): void;

    /**
     * Override dei meta SEO della pagina RSVP. Tornare un array con
     * eventuali chiavi: title, description, image (path o url assoluta).
     * Chiavi assenti → fallback ai default del modulo.
     *
     * @param string $page  'home' | 'login'
     * @param array<string, mixed> $state  Output di `Rsvp::context()`
     * @return array{title?:string, description?:string, image?:string}
     */
    public function seo(string $page, array $state): array;
}
