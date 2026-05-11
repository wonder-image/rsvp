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
use Wonder\Plugin\Rsvp\Models\InviteGroup;

final class InviteGroupResource extends Resource
{
    public static string $model = InviteGroup::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'gruppo inviti',
            'plural_label' => 'gruppi inviti',
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
            'name' => 'Nome',
            'description' => 'Descrizione',
            'invite_codes' => 'Codici',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormInput::key('code')->text()->required(),
            FormInput::key('name')->text()->required(),
            FormInput::key('description')->textarea(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('code')->columnSpan(4),
                static::getInput('name')->columnSpan(8),
                static::getInput('description')->columnSpan(12),
            ])->columns(12)->columnSpan(12),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('name')->text()->link('edit'),
            TableColumn::key('code')->text()->size('little'),
            TableColumn::key('invite_codes')->text()->function('rsvpInviteGroupCodes', 'id')->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Gruppi inviti')
            ->buttonAdd('Aggiungi gruppo')
            ->results()
            ->filters()
            ->searchFields(['name', 'code', 'description']);
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
            ->title('Gruppi inviti')
            ->order(30)
            ->authority(['admin']);
    }
}
