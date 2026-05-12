<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\Plugin\Rsvp\Contracts\RsvpExtension;

/**
 * Implementazione di default di RsvpExtension. Il consumer estende questa
 * classe e override solo i metodi che gli servono.
 */
abstract class AbstractRsvpExtension implements RsvpExtension
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
