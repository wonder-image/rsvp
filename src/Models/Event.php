<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\App\Support\SyncSchema;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;
use Wonder\App\Schema\Extensions\AddressExtension;

final class Event extends Model
{
    public static string $table = 'rsvp_event';
    public static string $folder = 'rsvp/events';
    public static string $icon = 'bi bi-calendar-event';

    public static function syncSchema(): ?SyncSchema
    {
        return SyncSchema::multiRow();
    }

    public static function tableSchema(): array
    {
        return [

            ...static::sqlColumnsFromDataSchema([
                'code',
                'name',
                'description',
                'starts_at',
                'location_name',
                'location_logo',
                'position',
                'active'
            ]),

            ...static::addressExtension()->tableSchema(),

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
            Field::key('description')->text(),
            Field::key('starts_at')->date(),
            Field::key('position')->number()->decimals(0),
            Field::key('active')->text()->required(),

            Field::key('location_name')->text(),
            Field::key('location_site_url')->text(),
            Field::key('location_logo')->file()->sanitize(false),
            ...static::addressExtension()->dataSchema()

        ];
    }

    public static function addressExtension(): AddressExtension
    {
        return AddressExtension::simple('location', 'position_url', 'it')
                ->withLink()
                ->requiredFields([ 'country', 'province', 'city', 'cap', 'street', 'number', 'link' ]);
    }

    public static function decorate($row): array
    {
        return static::addressExtension()->decorate($row);
    }

}
