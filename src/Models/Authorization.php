<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\App\Support\SyncSchema;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Authorization extends Model
{
    public static string $table = 'rsvp_authorization';
    public static string $folder = 'rsvp/authorizations';
    public static string $icon = 'bi bi-shield-check';

    public static function syncSchema(): ?SyncSchema
    {
        return SyncSchema::multiRow();
    }
    
    public static function tableSchema(): array
    {
        return [
            Column::key('code')->length(120)->unique(),
            Column::key('name')->length(255),
            Column::key('description')->type('TEXT')->null(),
            Column::key('visible_event_keys_json')->type('LONGTEXT')->null(),
            Column::key('max_participants')->type('INT')->null(),
            Column::key('allow_children')->length(10)->default('false'),
            Column::key('max_children')->type('INT')->null(),
        ];
    }

    public static function tablePseudos(): array
    {
        return [
            'ind_code' => [
                'index' => 'code',
            ],
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('code')->text()->required()->codeUpper(),
            Field::key('name')->text()->required()->sanitizeFirst(),
            Field::key('description')->text()->sanitize(false),
            Field::key('visible_event_keys_json')->text()->json()->sanitize(false),
            Field::key('max_participants')->number()->decimals(0),
            Field::key('allow_children')->text()->required(),
            Field::key('max_children')->number()->decimals(0),
        ];
    }
}
