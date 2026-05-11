<?php

use Wonder\Plugin\Rsvp\Support\ResponseExporter;

$format = trim((string) ($_GET['format'] ?? ($_REQUEST['format'] ?? '')));

if ($format === '' && isset($route['parameters']['format'])) {
    $format = trim((string) $route['parameters']['format']);
}

if ($format === '') {
    $format = trim((string) ($parameters['format'] ?? ''));
}

ResponseExporter::download($format, isset($_GET['event']) ? trim((string) $_GET['event']) : null);
