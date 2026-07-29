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
            Column::key('access')->length(10)->default('code'),
            Column::key('visible_event_ids')->type('LONGTEXT')->null(),
            Column::key('max_participants')->type('INT')->null(),
            Column::key('allow_children')->length(10)->default('false'),
            Column::key('max_children')->type('INT')->null(),
            Column::key('enable_attendance_status')->length(10)->default('false'),
            Column::key('require_image_release')->length(10)->default('false'),
            Column::key('field_age')->length(10)->default('required'),
            Column::key('field_sex')->length(10)->default('required'),
            Column::key('field_allergies')->length(10)->default('optional'),
            Column::key('field_company')->length(10)->default('required'),
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
            Field::key('access')->text()->required(),
            Field::key('visible_event_ids')->text()->json()->sanitize(false),
            Field::key('max_participants')->number()->decimals(0),
            Field::key('allow_children')->text()->required(),
            Field::key('max_children')->number()->decimals(0),
            Field::key('enable_attendance_status')->text(),
            Field::key('require_image_release')->text(),
            Field::key('field_age')->text(),
            Field::key('field_sex')->text(),
            Field::key('field_allergies')->text(),
            Field::key('field_company')->text(),
        ];
    }
}
