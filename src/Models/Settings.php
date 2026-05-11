<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Settings extends Model
{
    public static string $table = 'rsvp_settings';
    public static string $folder = 'rsvp/settings';
    public static string $icon = 'bi bi-gear';

    public static function tableSchema(): array
    {
        return [
            Column::key('event_name')->length(255)->null(),
            Column::key('event_starts_at')->type('DATETIME')->null(),
            Column::key('location_name')->length(255)->null(),
            Column::key('location_url')->type('TEXT')->null(),
            Column::key('require_invite_code')->length(10)->default('false'),
            Column::key('login_title')->length(255)->null(),
            Column::key('login_text')->type('LONGTEXT')->null(),
            Column::key('home_title')->length(255)->null(),
            Column::key('home_text')->type('LONGTEXT')->null(),
            Column::key('event_options_json')->type('LONGTEXT')->null(),
            Column::key('events_catalog_json')->type('LONGTEXT')->null(),
            Column::key('page_content_json')->type('LONGTEXT')->null(),
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
            Field::key('event_name')->text()->sanitize(false),
            Field::key('event_starts_at')->date(),
            Field::key('location_name')->text()->sanitize(false),
            Field::key('location_url')->text()->sanitize(false),
            Field::key('require_invite_code')->text(),
            Field::key('login_title')->text()->sanitize(false),
            Field::key('login_text')->text()->sanitize(false),
            Field::key('home_title')->text()->sanitize(false),
            Field::key('home_text')->text()->sanitize(false),
            Field::key('event_options_json')->text()->json()->sanitize(false),
            Field::key('events_catalog_json')->text()->json()->sanitize(false),
            Field::key('page_content_json')->text()->json()->sanitize(false),
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
}
