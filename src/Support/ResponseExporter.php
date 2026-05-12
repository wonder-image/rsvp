<?php

namespace Wonder\Plugin\Rsvp\Support;

use RuntimeException;
use Wonder\Plugin\Rsvp\Models\Response;

final class ResponseExporter
{
    public static function download(string $format, ?string $eventKey = null): never
    {
        $rows = self::rows($eventKey);
        $fileName = 'RSVP-'.date('Y-m-d-H-i');

        if ($eventKey !== null && trim($eventKey) !== '') {
            $fileName .= '-'.preg_replace('/[^a-z0-9_-]+/i', '-', trim($eventKey));
        }

        if ($format === 'csv') {
            arrayToCsv($rows, $fileName);
            exit();
        }

        if ($format === 'xls') {
            arrayToXls($rows, $fileName);
            exit();
        }

        throw new RuntimeException('Formato export non supportato.');
    }

    public static function rows(?string $eventKey = null): array
    {
        $customFields = Response::customFieldDefinitions();
        $filters = ['deleted' => 'false'];

        if ($eventKey !== null && trim($eventKey) !== '') {
            $filters['event_key'] = trim($eventKey);
        }

        $result = Response::query()->Select(Response::$table, $filters, null, 'creation', 'DESC');
        $header = [
            'Codice prenotazione',
            'Creato il',
            'Eventi selezionati',
            'Referente nome',
            'Referente cognome',
            'Referente email',
            'Referente telefono',
            'Partecipanti prenotazione',
            'Bambini prenotazione',
            'Partecipante nome',
            'Partecipante cognome',
            'Partecipante tipo',
            'Esigenze alimentari',
            'Codice invito',
            'Gruppo invito',
            'Autorizzazione',
            'Richieste',
            'Privacy',
            'Foto',
            'Lingua',
            'URL origine',
        ];

        foreach ($customFields as $field) {
            $header[] = (string) $field['label'];
        }

        $header[] = 'Metadati';
        $rows = [$header];

        foreach ((array) ($result->row ?? []) as $row) {
            $participants = rsvpDecodeJsonArray($row['participants_json'] ?? '[]');
            $events = rsvpDecodeJsonArray($row['events_json'] ?? '[]');
            $consents = rsvpDecodeJsonArray($row['consents_json'] ?? '[]');
            $metadata = rsvpMetadataSummary($row['metadata_json'] ?? '[]');
            $bookingCode = trim((string) ($row['booking_code'] ?? ''));

            if ($bookingCode === '') {
                $bookingCode = Response::bookingCodeFromId($row['id'] ?? null);
            }

            if ($participants === []) {
                $participants[] = [
                    'name' => '',
                    'surname' => '',
                    'dietary_requirements' => '',
                    'is_child' => false,
                ];
            }

            $eventSummary = $events !== []
                ? implode(', ', array_map('strval', $events))
                : (string) ($row['event_key'] ?? '');

            foreach ($participants as $participant) {
                $rows[] = [
                    $bookingCode,
                    (string) ($row['creation'] ?? ''),
                    $eventSummary,
                    (string) ($row['contact_name'] ?? ''),
                    (string) ($row['contact_surname'] ?? ''),
                    (string) ($row['contact_email'] ?? ''),
                    (string) ($row['contact_phone'] ?? ''),
                    (string) ($row['participants_count'] ?? ''),
                    (string) ($row['children_count'] ?? ''),
                    (string) ($participant['name'] ?? ''),
                    (string) ($participant['surname'] ?? ''),
                    !empty($participant['is_child']) ? 'Bambino' : 'Adulto',
                    (string) ($participant['dietary_requirements'] ?? ''),
                    (string) ($row['invite_code'] ?? ''),
                    (string) ($row['invite_group_code'] ?? ''),
                    (string) ($row['authorization_code'] ?? ''),
                    (string) ($row['notes'] ?? ''),
                    rsvpBooleanText($consents['privacy'] ?? false),
                    rsvpBooleanText($consents['photo'] ?? false),
                    (string) ($row['locale'] ?? ''),
                    (string) ($row['source_url'] ?? ''),
                ];

                foreach ($customFields as $field) {
                    $rows[count($rows) - 1][] = (string) ($row[$field['column']] ?? '');
                }

                $rows[count($rows) - 1][] = $metadata;
            }
        }

        return $rows;
    }
}
