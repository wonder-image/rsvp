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
 * I custom field finiscono nel campo `metadata_json` della tabella
 * `rsvp_response`: niente migration sullo schema del consumer.
 */
interface RsvpExtension
{
    /**
     * Custom field da aggiungere al form RSVP (oltre ai campi standard).
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
}
