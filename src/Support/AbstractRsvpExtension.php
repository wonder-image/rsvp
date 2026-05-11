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
