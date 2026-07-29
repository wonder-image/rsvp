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
            Column::key('check_duplicate_submission')->length(10)->default('false'),
            Column::key('admin_email')->length(255)->null(),
            Column::key('admin_notifications')->length(10)->default('true'),
            Column::key('customer_notifications')->length(10)->default('true'),
            Column::key('customer_subject')->length(255)->null(),
            Column::key('customer_message')->type('LONGTEXT')->null(),
            Column::key('admin_subject')->length(255)->null(),
            Column::key('admin_message')->type('LONGTEXT')->null(),
            // Varianti email per risposta "non partecipa" (attendance declined).
            Column::key('admin_declined_notifications')->length(10)->default('true'),
            Column::key('customer_declined_notifications')->length(10)->default('true'),
            Column::key('customer_declined_subject')->length(255)->null(),
            Column::key('customer_declined_message')->type('LONGTEXT')->null(),
            Column::key('admin_declined_subject')->length(255)->null(),
            Column::key('admin_declined_message')->type('LONGTEXT')->null(),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('poster')->file()->sanitize(false),
            Field::key('check_duplicate_submission')->text(),
            Field::key('admin_email')->email(),
            Field::key('admin_notifications')->text(),
            Field::key('customer_notifications')->text(),
            Field::key('customer_subject')->text()->sanitize(false),
            Field::key('customer_message')->text()->sanitize(false),
            Field::key('admin_subject')->text()->sanitize(false),
            Field::key('admin_message')->text()->sanitize(false),
            Field::key('admin_declined_notifications')->text(),
            Field::key('customer_declined_notifications')->text(),
            Field::key('customer_declined_subject')->text()->sanitize(false),
            Field::key('customer_declined_message')->text()->sanitize(false),
            Field::key('admin_declined_subject')->text()->sanitize(false),
            Field::key('admin_declined_message')->text()->sanitize(false),
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
