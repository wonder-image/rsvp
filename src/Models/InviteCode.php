<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class InviteCode extends Model
{
    public static string $table = 'rsvp_invite_code';
    public static string $folder = 'rsvp/invite-codes';
    public static string $icon = 'bi bi-ticket-perforated';

    public static function tableSchema(): array
    {
        return [
            Column::key('code')->length(120)->unique(),
            Column::key('usage_mode')->length(30)->default('multiple_use'),
            Column::key('authorization_id')->int()->null()->foreign(Authorization::$table),
            Column::key('invite_group_id')->int()->null()->foreign(InviteGroup::$table),
            Column::key('active')->length(10)->default('true'),
            Column::key('notes')->type('TEXT')->null(),
        ];
    }

    public static function tablePseudos(): array
    {
        return [
            'ind_code_active' => [
                'index' => ['code', 'active'],
            ],
            'ind_group' => [
                'index' => 'invite_group_id',
            ],
            'ind_authorization' => [
                'index' => 'authorization_id',
            ],
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('code')->text()->required(),
            Field::key('usage_mode')->text()->required(),
            Field::key('authorization_id')->number()->decimals(0),
            Field::key('invite_group_id')->number()->decimals(0),
            Field::key('active')->text()->required(),
            Field::key('notes')->text()->sanitize(false),
        ];
    }
}
