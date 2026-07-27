<?php

namespace Wonder\Plugin\Rsvp\Services;

use RuntimeException;
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Resources\ResponseResource;

/**
 * Export delle risposte RSVP con cardinalità una riga per partecipante.
 *
 * Le colonne restano dichiarate dalla Resource tramite
 * TableLayoutSchema::downloadColumns(); questo servizio cambia soltanto la
 * cardinalità delle righe, cosa che il downloader generico del framework non
 * supporta.
 */
final class ResponseExporter
{
    public static function download(string $format): never
    {
        $format = strtolower(trim($format));

        if (!in_array($format, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Formato export non supportato: '.$format);
        }

        $layout = ResponseResource::tableLayoutSchema()->all();
        $download = (array) ($layout['download'] ?? []);
        $formats = (array) ($download['formats'] ?? []);

        if (empty($download['enabled']) || !array_key_exists($format, $formats)) {
            throw new RuntimeException('Formato export non abilitato: '.$format);
        }

        $query = ResponseResource::querySchema();
        $order = (array) ($query['order'] ?? []);
        $responses = Response::find(
            $query['condition'] ?? null,
            null,
            (string) ($order['column'] ?? 'creation'),
            (string) ($order['direction'] ?? 'DESC')
        );

        $matrix = self::matrix(
            is_array($responses) ? $responses : [],
            (array) ($download['columns'] ?? [])
        );

        $filename = trim((string) ($download['filename'] ?? ''));

        if ($filename === '') {
            $filename = ResponseResource::slug().'-'.date('Y-m-d');
        }

        if ($format === 'csv') {
            arrayToCsv($matrix, $filename);
            exit();
        }

        arrayToXlsx($matrix, $filename);
        exit();
    }

    /**
     * Costruisce la matrice finale riusando le colonne normalizzate del
     * TableLayoutSchema.
     *
     * @param array<int|string, mixed> $responses
     * @param array<int, mixed> $columns
     * @return array<int, array<int, mixed>>
     */
    public static function matrix(array $responses, array $columns): array
    {
        $columns = self::resolveColumns($columns);
        $matrix = [array_map(
            static fn (array $column): string => $column['label'],
            $columns
        )];

        foreach (self::expandRows($responses) as $row) {
            $matrix[] = self::buildRow($columns, $row);
        }

        return $matrix;
    }

    /**
     * Espande ciascuna risposta in una riga per partecipante, duplicando i
     * dati della prenotazione. Le risposte senza partecipanti mantengono una
     * riga di fallback, così i rifiuti non scompaiono dall'export.
     *
     * @param array<int|string, mixed> $responses
     * @return array<int, array<string, mixed>>
     */
    public static function expandRows(array $responses): array
    {
        $expanded = [];

        foreach (self::recordList($responses) as $response) {
            $participants = rsvpDecodeJsonArray($response['participants'] ?? '[]');

            if (!array_is_list($participants) && self::looksLikeParticipant($participants)) {
                $participants = [$participants];
            }

            $participants = array_values(array_filter(
                $participants,
                static fn (mixed $participant): bool => is_array($participant)
                    && trim(
                        self::text($participant['name'] ?? '')
                        .self::text($participant['surname'] ?? '')
                        .self::text($participant['dietary_requirements'] ?? ($participant['allergies'] ?? ''))
                    ) !== ''
            ));

            $fallback = $participants === [];

            if ($fallback) {
                $participants = [[
                    'name' => self::text($response['contact_name'] ?? ''),
                    'surname' => self::text($response['contact_surname'] ?? ''),
                    'dietary_requirements' => '',
                    'is_child' => false,
                ]];
            }

            foreach ($participants as $index => $participant) {
                $isChild = self::boolean($participant['is_child'] ?? false);
                $dietaryRequirements = self::text(
                    $participant['dietary_requirements'] ?? ($participant['allergies'] ?? '')
                );
                $type = $isChild ? 'Bambino' : 'Adulto';

                if (
                    $fallback
                    && rsvpAttendanceStatusValue($response['attendance_status'] ?? null) === 'declined'
                ) {
                    $type = 'Non partecipa';
                }

                $expanded[] = array_replace($response, [
                    'export_participant_number' => $index + 1,
                    'export_participant_name' => self::text($participant['name'] ?? ''),
                    'export_participant_surname' => self::text($participant['surname'] ?? ''),
                    'export_participant_age' => self::text($participant['age'] ?? ''),
                    'export_participant_sex' => rsvpSexText($participant['sex'] ?? ''),
                    'export_participant_type' => $type,
                    'export_participant_dietary_requirements' => $dietaryRequirements,
                    'export_participant_is_child' => $isChild,
                ]);
            }
        }

        return $expanded;
    }

    /**
     * @param array<int|string, mixed> $responses
     * @return array<int, array<string, mixed>>
     */
    private static function recordList(array $responses): array
    {
        if ($responses === []) {
            return [];
        }

        if (!array_is_list($responses)) {
            return [$responses];
        }

        return array_values(array_filter($responses, 'is_array'));
    }

    /**
     * @param array<int, mixed> $columns
     * @return array<int, array{field:string,label:string,value:mixed}>
     */
    private static function resolveColumns(array $columns): array
    {
        $resolved = [];

        foreach ($columns as $column) {
            if (is_string($column) && trim($column) !== '') {
                $column = [
                    'field' => trim($column),
                    'label' => null,
                    'value' => null,
                ];
            }

            if (!is_array($column)) {
                continue;
            }

            $value = $column['value'] ?? null;
            $field = trim((string) ($column['field'] ?? (is_string($value) ? $value : '')));

            if ($field === '' && !is_callable($value)) {
                continue;
            }

            $label = isset($column['label']) && is_string($column['label'])
                ? trim($column['label'])
                : '';

            if ($label === '') {
                $label = (string) (ResponseResource::getLabel($field) ?: self::titleize($field));
            }

            $resolved[] = [
                'field' => $field,
                'label' => $label,
                'value' => $value,
            ];
        }

        return $resolved;
    }

    /**
     * @param array<int, array{field:string,label:string,value:mixed}> $columns
     * @param array<string, mixed> $row
     * @return array<int, mixed>
     */
    private static function buildRow(array $columns, array $row): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $value = $column['value'];

            if (is_callable($value)) {
                $cells[] = $value($row);
                continue;
            }

            $field = is_string($value) && $value !== '' ? $value : $column['field'];
            $cells[] = $row[$field] ?? '';
        }

        return $cells;
    }

    private static function looksLikeParticipant(array $value): bool
    {
        return array_key_exists('name', $value)
            || array_key_exists('surname', $value)
            || array_key_exists('dietary_requirements', $value)
            || array_key_exists('is_child', $value);
    }

    private static function boolean(mixed $value): bool
    {
        if (is_array($value)) {
            $value = $value[0] ?? false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function titleize(string $field): string
    {
        $clean = trim(str_replace(['_', '-'], ' ', $field));

        return $clean === '' ? '' : (function_exists('mb_convert_case')
            ? mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8')
            : ucwords($clean));
    }
}
