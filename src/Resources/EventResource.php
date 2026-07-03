<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormField;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PageSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;
use Wonder\Elements\Components\{Card, SectionTitle, Container};
use Wonder\Elements\Form\Form;
use Wonder\Plugin\Rsvp\Models\Event;

final class EventResource extends Resource
{
    public static string $model = Event::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'evento',
            'plural_label' => 'eventi',
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
            'location_name' => 'Nome',
            ...static::$model::addressExtension()->labels(),
            'location_position_url' => 'Link posizione',
            'location_site_url' => 'Link sito',
            'location_logo' => 'Logo',
            'position' => 'Ordine',
            'active' => 'Attivo',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormField::key('code')->text()->required(),
            FormField::key('name')->text()->required(),
            FormField::key('description')->textarea(),
            FormField::key('starts_at')->textDatetime(),
            FormField::key('location_name')->text(),
            ...static::$model::addressExtension()->formSchema(),
            FormField::key('location_site_url')->url(),
            FormField::key('location_logo')->fileDragDrop(),
            FormField::key('position')->position(),
            FormField::key('active')->bool(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Container)->components([

                (new Card)->components([
                    
                    static::getInput('code')->columnSpan(3),
                    static::getInput('name')->columnSpan(6),
                    static::getInput('starts_at')->columnSpan(3),
                    static::getInput('description')->columnSpan(12)

                ])->columns(12)->columnSpan(12),

                (new Card)->components([

                    (new SectionTitle('Location'))->columnSpan(12),

                    (new Container)->components([
                        static::getInput('location_logo')->columnSpan(1)
                    ])->columnSpan(4)->columns(1),

                    (new Container)->components([

                        static::getInput('location_name')->columnSpan(12),
                    
                        static::getInput('location_country')->columnSpan(6),
                        static::getInput('location_province')->columnSpan(6),
                        static::getInput('location_cap')->columnSpan(4),
                        static::getInput('location_city')->columnSpan(8),
                        static::getInput('location_street')->columnSpan(10),
                        static::getInput('location_number')->columnSpan(2),
                        static::getInput('location_position_url')->columnSpan(12),
                        static::getInput('location_site_url')->columnSpan(12),

                    ])->columnSpan(8)->columns(12),
                    

                ])->columns(12)->columnSpan(12),

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
            TableColumn::key('code')->text()->link('edit')->size('medium'),
            TableColumn::key('name')->text(),
            TableColumn::key('starts_at')->datetime(),
            TableColumn::key('position')->text()->size('little'),
            TableColumn::key('active')->activeBadge()->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'active', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Eventi')
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
            ->inSection('rsvp')
            ->title('Eventi')
            ->order(9)
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
