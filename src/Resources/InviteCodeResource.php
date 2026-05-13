<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PageSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;
use Wonder\Elements\Components\Card;
use Wonder\Elements\Form\Form;
use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\InviteCode;
use Wonder\Plugin\Rsvp\Models\InviteGroup;

final class InviteCodeResource extends Resource
{
    public static string $model = InviteCode::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'codice invito',
            'plural_label' => 'codici invito',
            'last' => 'ultimi',
            'all' => 'tutti',
            'article' => 'i',
            'full' => 'usato',
            'empty' => 'vuoto',
            'this' => 'questo',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'code' => 'Codice',
            'usage_mode' => 'Tipologia',
            'authorization_id' => 'Autorizzazione',
            'invite_group_id' => 'Gruppo',
            'active' => 'Stato',
            'notes' => 'Note',
            'used_count' => 'Usi',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormInput::key('code')->textGenerator()->required(),
            FormInput::key('authorization_id')->select(static::authorizationOptions()),
            FormInput::key('invite_group_id')->select(static::groupOptions()),
            FormInput::key('usage_mode')->select([
                'single_use' => 'Monouso',
                'multiple_use' => 'Multiuso',
            ])->required()->value('multiple_use'),
            FormInput::key('active')->select([
                'true' => 'Attivo',
                'false' => 'Disattivato',
            ])->required()->value('true'),
            FormInput::key('notes')->textarea(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('code')->columnSpan(6),
                static::getInput('authorization_id')->columnSpan(6),
                static::getInput('invite_group_id')->columnSpan(6),
                static::getInput('notes')->columnSpan(12),
            ])->columns(12)->columnSpan(9),
            (new Card)->components([
                static::getInput('usage_mode')->columnSpan(12),
                static::getInput('active')->columnSpan(12),
            ])->columns(12)->columnSpan(3),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('code')->text()->link('edit'),
            TableColumn::key('authorization_id')->text()->function('rsvpAuthorizationLabel', 'authorization_id')->size('medium'),
            TableColumn::key('invite_group_id')->text()->function('rsvpInviteGroupLabel', 'invite_group_id')->size('medium'),
            TableColumn::key('usage_mode')->badge()->function('rsvpInviteUsageMode', 'usage_mode', 'automaticResize'),
            TableColumn::key('used_count')->text()->function('rsvpInviteCodeResponses', 'id')->size('little'),
            TableColumn::key('active')->badge()->function('active', 'id', 'automaticResize'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Codici invito')
            ->buttonAdd('Aggiungi codice')
            ->results()
            ->filters()
            ->searchFields(['code', 'notes'])
            ->filterRadio('Tipologia', 'usage_mode', [
                '' => 'Tutte',
                'single_use' => 'Monouso',
                'multiple_use' => 'Multiuso',
            ])
            ->filterRadio('Stato', 'active', [
                '' => 'Tutti',
                'true' => 'Attivi',
                'false' => 'Disattivati',
            ]);
    }

    public static function pageSchema(): PageSchema
    {
        return PageSchema::for(static::class)
            ->disable('view');
    }

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)->enabled(false);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backend(['list', 'create', 'store', 'edit', 'update', 'delete'], ['admin']);
    }

    public static function navigationSchema(): NavigationSchema
    {
        return NavigationSchema::for(static::class)
            ->section('RSVP', 'rsvp', 'bi-ticket-perforated')
            ->title('Codici invito')
            ->order(20)
            ->authority(['admin']);
    }

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        if (trim((string) ($values['code'] ?? '')) === '') {
            $values['code'] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $values;
    }

    private static function groupOptions(): array
    {
        $options = ['' => 'Nessun gruppo'];
        $groups = InviteGroup::query()->Select(InviteGroup::$table, ['deleted' => 'false']);

        foreach ((array) ($groups->row ?? []) as $row) {
            $options[(string) ($row['id'] ?? '')] = (string) ($row['name'] ?? $row['code'] ?? '');
        }

        return $options;
    }

    private static function authorizationOptions(): array
    {
        $options = ['' => 'Nessuna autorizzazione'];
        $authorizations = Authorization::query()->Select(Authorization::$table, ['deleted' => 'false']);

        foreach ((array) ($authorizations->row ?? []) as $row) {
            $options[(string) ($row['id'] ?? '')] = (string) ($row['name'] ?? $row['code'] ?? '');
        }

        return $options;
    }
}
