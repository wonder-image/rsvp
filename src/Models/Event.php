<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Event extends Model
{
    public static string $table = 'rsvp_event';
    public static string $folder = 'rsvp/events';
    public static string $icon = 'bi bi-calendar-event';

    public static function tableSchema(): array
    {
        return [
            Column::key('code')->length(120)->unique(),
            Column::key('name')->length(255),
            Column::key('description')->type('TEXT')->null(),
            Column::key('starts_at')->type('DATETIME')->null(),
            Column::key('location_name')->length(255)->null(),
            Column::key('location_address')->type('TEXT')->null(),
            Column::key('location_address_url')->type('TEXT')->null(),
            Column::key('location_position_url')->type('TEXT')->null(),
            Column::key('location_logo')->type('TEXT')->null(),
            Column::key('position')->type('INT')->default('0'),
            Column::key('active')->length(10)->default('true'),
        ];
    }

    public static function tablePseudos(): array
    {
        return [
            'ind_code' => [
                'index' => 'code',
            ],
            'ind_position' => [
                'index' => 'position',
            ],
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('code')->text()->required()->codeUpper(),
            Field::key('name')->text()->required()->sanitizeFirst(),
            Field::key('description')->text()->sanitize(false),
            Field::key('starts_at')->date(),
            Field::key('location_name')->text()->sanitize(false),
            Field::key('location_address')->text()->sanitize(false),
            Field::key('location_address_url')->text()->sanitize(false),
            Field::key('location_position_url')->text()->sanitize(false),
            Field::key('location_logo')->text()->sanitize(false),
            Field::key('position')->number()->decimals(0),
            Field::key('active')->text()->required(),
        ];
    }
}
