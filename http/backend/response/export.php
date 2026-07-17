<?php

use Wonder\Plugin\Rsvp\Services\ResponseExporter;

$routeParameters = is_array($ROUTE_PARAMETERS ?? null) ? $ROUTE_PARAMETERS : [];
$format = trim((string) ($routeParameters['format'] ?? ''));

if ($format === '') {
    throw new RuntimeException('Formato export RSVP mancante.');
}

ResponseExporter::download($format);
