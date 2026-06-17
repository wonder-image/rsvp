<?php

namespace Wonder\Plugin\Rsvp\Contracts;

use Wonder\App\ResourceSchema\FormField;

/**
 * Contratto opzionale per estensioni RSVP che vogliono dichiarare i custom
 * field come lista di FormField invece che come array normalizzati legacy.
 */
interface SupportsFormInputs
{
    /**
     * Field visibili nel form RSVP del contesto corrente.
     *
     * @return array<int, FormField>
     */
    public function formInputs(): array;

    /**
     * SUPER-SET di tutti i field possibili, usato dallo schema sync per creare
     * le colonne `meta_<key>` necessarie.
     *
     * @return array<int, FormField>
     */
    public function allFormInputs(): array;
}
