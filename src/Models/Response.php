<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;
use Wonder\Sql\TableSchema as Column;

final class Response extends Model
{
    public static string $table = 'rsvp_response';
    public static string $folder = 'rsvp/responses';
    public static string $icon = 'bi bi-envelope-check';

    public static function tableSchema(): array
    {
        $schema = [
            Column::key('booking_code')->length(20)->null(),
            Column::key('invite_code_id')->int()->null()->foreign(InviteCode::$table),
            Column::key('invite_code')->length(120)->null(),
            Column::key('invite_group_code')->length(120)->null(),
            Column::key('authorization_code')->length(120)->null(),
            Column::key('event_key')->length(120)->null(),
            Column::key('locale')->length(20)->null(),
            Column::key('contact_name')->length(255),
            Column::key('contact_surname')->length(255)->null(),
            Column::key('contact_phone')->length(80)->null(),
            Column::key('contact_email')->length(255),
            Column::key('participants_json')->type('LONGTEXT'),
            Column::key('participants_count')->type('INT')->default('1'),
            Column::key('children_count')->type('INT')->default('0'),
            Column::key('notes')->type('LONGTEXT')->null(),
            Column::key('events_json')->json()->null(),
            Column::key('consents_json')->json()->null(),
            Column::key('legal_documents_json')->json()->null(),
            Column::key('metadata_json')->type('LONGTEXT')->null(),
            Column::key('source_url')->type('TEXT')->null(),
        ];

        foreach (self::customFieldDefinitions() as $field) {
            $schema[] = Column::key((string) $field['column'])->type('LONGTEXT')->null();
        }

        return $schema;
    }

    public static function tablePseudos(): array
    {
        return [
            'ind_event_key' => [
                'index' => 'event_key',
            ],
            'ind_booking_code' => [
                'index' => 'booking_code',
            ],
            'ind_contact_email' => [
                'index' => 'contact_email',
            ],
            'ind_invite_code_id' => [
                'index' => 'invite_code_id',
            ],
            'ind_authorization_code' => [
                'index' => 'authorization_code',
            ],
        ];
    }

    public static function dataSchema(): array
    {
        $schema = [
            Field::key('booking_code')->text()->sanitize(false),
            Field::key('invite_code_id')->number()->decimals(0),
            Field::key('invite_code')->text()->sanitize(false),
            Field::key('invite_group_code')->text()->sanitize(false),
            Field::key('authorization_code')->text()->sanitize(false),
            Field::key('event_key')->text()->sanitize(false),
            Field::key('locale')->text()->sanitize(false),
            Field::key('contact_name')->text()->required()->sanitizeFirst(),
            Field::key('contact_surname')->text()->sanitizeFirst(),
            Field::key('contact_phone')->text(),
            Field::key('contact_email')->email()->required(),
            Field::key('participants_json')->text()->required()->json()->sanitize(false),
            Field::key('participants_count')->number()->required()->decimals(0),
            Field::key('children_count')->number()->required()->decimals(0),
            Field::key('notes')->text()->sanitize(false),
            Field::key('events_json')->text()->json()->sanitize(false),
            Field::key('consents_json')->text()->json()->sanitize(false),
            Field::key('legal_documents_json')->text()->json()->sanitize(false),
            Field::key('metadata_json')->text()->json()->sanitize(false),
            Field::key('source_url')->text()->sanitize(false),
        ];

        foreach (self::customFieldDefinitions() as $field) {
            $schema[] = Field::key((string) $field['column'])->text()->sanitize(false);
        }

        return $schema;
    }

    /**
     * @return array<string, array{key:string,label:string,type:string,column:string}>
     */
    public static function customFieldDefinitions(): array
    {
        // SUPER-SET di tutti i campi possibili: usato per definire le
        // colonne `meta_<key>` sulla tabella. La versione session-aware
        // (ExtensionRegistry::fields()) si userebbe per rendering, ma
        // qui dobbiamo materializzare TUTTE le colonne anche se al
        // momento della migrazione non c'è una sessione attiva.
        $fields = ExtensionRegistry::allFields();
        $definitions = [];

        foreach ($fields as $key => $field) {
            $definitions[(string) $key] = [
                'key' => (string) $key,
                'label' => (string) ($field['label'] ?? $key),
                'type' => (string) ($field['type'] ?? 'text'),
                'column' => rsvpCustomFieldColumn((string) $key),
            ];
        }

        return $definitions;
    }

    public static function bookingCodeFromId(int|string|null $id): string
    {
        $id = (int) $id;

        if ($id <= 0) {
            return '';
        }

        return 'pre_'.str_pad((string) $id, 7, '0', STR_PAD_LEFT);
    }

    public static function persistBookingCode(int|string|null $id): string
    {
        $id = (int) $id;

        if ($id <= 0) {
            return '';
        }

        $bookingCode = self::bookingCodeFromId($id);

        if ($bookingCode === '') {
            return '';
        }

        self::query()->Update(
            self::$table,
            ['booking_code' => $bookingCode],
            'id',
            $id
        );

        return $bookingCode;
    }

    public static function resolveBookingCode(array $row, bool $persistIfMissing = true): string
    {
        $bookingCode = trim((string) ($row['booking_code'] ?? ''));

        if ($bookingCode !== '') {
            return $bookingCode;
        }

        $id = (int) ($row['id'] ?? 0);
        $bookingCode = self::bookingCodeFromId($id);

        if ($persistIfMissing && $bookingCode !== '' && $id > 0) {
            self::query()->Update(
                self::$table,
                ['booking_code' => $bookingCode],
                'id',
                $id
            );
        }

        return $bookingCode;
    }
}
