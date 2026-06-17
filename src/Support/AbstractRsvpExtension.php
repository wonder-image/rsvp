<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\App\ResourceSchema\FormField;
use Wonder\Plugin\Rsvp\Contracts\RsvpExtension;
use Wonder\Plugin\Rsvp\Contracts\SupportsFormInputs;

/**
 * Implementazione di default di RsvpExtension. Il consumer estende questa
 * classe e override solo i metodi che gli servono.
 */
abstract class AbstractRsvpExtension implements RsvpExtension, SupportsFormInputs
{
    public function fields(): array
    {
        return [];
    }

    public function allFields(): array
    {
        // Default backward-compat: stesso set di fields(). Override se
        // fields() filtra per sessione/ruolo: ritorna qui SEMPRE l'unione
        // di tutti i campi possibili, così lo schema sync crea le colonne.
        return $this->fields();
    }

    /**
     * @return array<int, FormField>
     */
    public function formInputs(): array
    {
        return [];
    }

    /**
     * @return array<int, FormField>
     */
    public function allFormInputs(): array
    {
        // Default backward-compat con la modalità FormField: stesso set di
        // formInputs(). Override se il rendering è session-aware ma lo schema
        // deve conoscere l'unione completa dei field possibili.
        return $this->formInputs();
    }

    public function beforeSubmit(array $payload): array
    {
        return $payload;
    }

    public function afterSubmit(array $response, array $payload): void
    {
        // no-op
    }

    public function seo(string $page, array $state): array
    {
        return [];
    }
}
