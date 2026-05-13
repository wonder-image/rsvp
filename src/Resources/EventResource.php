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
use Wonder\Plugin\Rsvp\Models\Event;

final class EventResource extends Resource
{
    public static string $model = Event::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'evento RSVP',
            'plural_label' => 'eventi RSVP',
            'last' => 'ultimi',
            'all' => 'tutti',
            'article' => 'gli',
            'full' => 'pieni',
            'empty' => 'vuoti',
            'this' => 'questo',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'code' => 'Codice',
            'name' => 'Nome',
            'description' => 'Descrizione',
            'starts_at' => 'Data evento',
            'location_name' => 'Location',
            'location_address' => 'Indirizzo',
            'location_address_url' => 'Link indirizzo',
            'location_position_url' => 'Link posizione',
            'location_logo' => 'Logo location',
            'position' => 'Ordine',
            'active' => 'Attivo',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormInput::key('code')->text()->required(),
            FormInput::key('name')->text()->required(),
            FormInput::key('description')->textarea(),
            FormInput::key('starts_at')->textDatetime(),
            FormInput::key('location_name')->text(),
            FormInput::key('location_address')->textarea(),
            FormInput::key('location_address_url')->url(),
            FormInput::key('location_position_url')->url(),
            FormInput::key('location_logo')->url(),
            FormInput::key('position')->number()->value('0'),
            FormInput::key('active')->select([
                'true' => 'Sì',
                'false' => 'No',
            ])->required()->value('true'),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('code')->columnSpan(3),
                static::getInput('name')->columnSpan(6),
                static::getInput('starts_at')->columnSpan(3),
                static::getInput('description')->columnSpan(12),
                static::getInput('location_name')->columnSpan(6),
                static::getInput('location_address')->columnSpan(6),
                static::getInput('location_address_url')->columnSpan(6),
                static::getInput('location_position_url')->columnSpan(6),
                static::getInput('location_logo')->columnSpan(12),
            ])->columns(12)->columnSpan(9),
            (new Card)->components([
                static::getInput('position')->columnSpan(12),
                static::getInput('active')->columnSpan(12),
            ])->columns(12)->columnSpan(3),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('code')->text()->link('edit'),
            TableColumn::key('name')->text()->link('edit'),
            TableColumn::key('starts_at')->datetime(),
            TableColumn::key('position')->text()->size('little'),
            TableColumn::key('active')->text()->function('rsvpBooleanText', 'active')->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Eventi RSVP')
            ->buttonAdd('Aggiungi evento')
            ->results()
            ->filters()
            ->searchFields(['code', 'name', 'description', 'location_name']);
    }

    public static function pageSchema(): PageSchema
    {
        return PageSchema::for(static::class)->disable('view');
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
            ->title('Eventi')
            ->order(10)
            ->authority(['admin']);
    }

    public static function mutateFormValues(
        array $values,
        string $mode,
        string $context = 'backend'
    ): array {
        if (!empty($values['starts_at']) && strtotime((string) $values['starts_at']) !== false) {
            $values['starts_at'] = date('Y-m-d\TH:i', strtotime((string) $values['starts_at']));
        }

        return $values;
    }
}
