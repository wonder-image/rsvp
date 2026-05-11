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
        $fields = ExtensionRegistry::fields();
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
}
