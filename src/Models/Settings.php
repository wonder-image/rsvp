<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\App\Support\MediaFileManager;
use Wonder\App\Support\SyncSchema;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Settings extends Model
{
    public static string $table = 'rsvp_settings';
    public static string $folder = 'rsvp/settings';
    public static string $icon = 'bi bi-gear';

    public static function syncSchema(): ?SyncSchema
    {
        return SyncSchema::multiRow()->exclude(['poster']);
    }
    
    public static function tableSchema(): array
    {
        return [
            Column::key('poster')->length(1000)->null(),
            Column::key('require_invite_code')->length(10)->default('false'),
            Column::key('enable_attendance_status')->length(10)->default('false'),
            Column::key('max_participants')->type('INT')->default('1'),
            Column::key('allow_children')->length(10)->default('false'),
            Column::key('max_children')->type('INT')->default('0'),
            Column::key('require_image_release')->length(10)->default('false'),
            Column::key('admin_email')->length(255)->null(),
            Column::key('admin_notifications')->length(10)->default('true'),
            Column::key('customer_notifications')->length(10)->default('true'),
            Column::key('customer_subject')->length(255)->null(),
            Column::key('customer_message')->type('LONGTEXT')->null(),
            Column::key('admin_subject')->length(255)->null(),
            Column::key('admin_message')->type('LONGTEXT')->null(),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('poster')->file()->sanitize(false),
            Field::key('require_invite_code')->text(),
            Field::key('enable_attendance_status')->text(),
            Field::key('max_participants')->number()->decimals(0),
            Field::key('allow_children')->text(),
            Field::key('max_children')->number()->decimals(0),
            Field::key('require_image_release')->text(),
            Field::key('admin_email')->email(),
            Field::key('admin_notifications')->text(),
            Field::key('customer_notifications')->text(),
            Field::key('customer_subject')->text()->sanitize(false),
            Field::key('customer_message')->text()->sanitize(false),
            Field::key('admin_subject')->text()->sanitize(false),
            Field::key('admin_message')->text()->sanitize(false),
        ];
    }

    public static function decorate($row): array
    {
        $urls = static::storedFileUrls(
            MediaFileManager::decodeStoredFiles($row['poster'] ?? ''),
            []
        );

        $row['poster_url'] = $urls[0] ?? '';

        return $row;
    }
}
