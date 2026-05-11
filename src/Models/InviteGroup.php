<?php

namespace Wonder\Plugin\Rsvp\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class InviteGroup extends Model
{
    public static string $table = 'rsvp_invite_group';
    public static string $folder = 'rsvp/invite-groups';
    public static string $icon = 'bi bi-diagram-3';

    public static function tableSchema(): array
    {
        return [
            Column::key('code')->length(120)->unique(),
            Column::key('name')->length(255),
            Column::key('description')->type('TEXT')->null(),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('code')->text()->required()->codeUpper(),
            Field::key('name')->text()->required()->sanitizeFirst(),
            Field::key('description')->text()->sanitize(false),
        ];
    }
}
