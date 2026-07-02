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
use Wonder\Elements\Components\Card;
use Wonder\Elements\Form\Form;
use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\Event;

final class AuthorizationResource extends Resource
{
    public static string $model = Authorization::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'autorizzazione',
            'plural_label' => 'autorizzazioni',
            'last' => 'ultime',
            'all' => 'tutte',
            'article' => 'le',
            'full' => 'piene',
            'empty' => 'vuote',
            'this' => 'questa',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'code' => 'Codice',
            'name' => 'Nome',
            'description' => 'Descrizione',
            'visible_event_ids' => 'Eventi visibili',
            'max_participants' => 'Max adulti',
            'allow_children' => 'Bambini',
            'max_children' => 'Max bambini',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormField::key('code')->text()->required(),
            FormField::key('name')->text()->required(),
            FormField::key('description')->textarea(),
            FormField::key('visible_event_ids')->checkbox()->options(static::eventOptions())->multiple()->required(),
            FormField::key('max_participants')->number(),
            FormField::key('allow_children')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->required()->value('false'),
            FormField::key('max_children')->number(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('code')->columnSpan(4),
                static::getInput('name')->columnSpan(8),
                static::getInput('description')->columnSpan(12),
                static::getInput('visible_event_ids')->columnSpan(12),
            ])->columns(12)->columnSpan(9),
            (new Card)->components([
                static::getInput('max_participants')->columnSpan(12),
                static::getInput('allow_children')->columnSpan(12),
                static::getInput('max_children')->columnSpan(12),
            ])->columns(12)->columnSpan(3),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('code')->text()->link('edit'),
            TableColumn::key('name')->text(),
            TableColumn::key('visible_event_ids')->text()->function('rsvpEventNamesFromIds', 'visible_event_ids')->size('medium'),
            TableColumn::key('max_participants')->text()->size('medium'),
            TableColumn::key('max_children')->text()->size('medium'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Autorizzazioni RSVP')
            ->buttonAdd('Aggiungi autorizzazione')
            ->results()
            ->filters()
            ->searchFields(['code', 'name', 'description']);
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
            ->title('Autorizzazioni')
            ->order(7)
            ->authority([ 'admin' ]);
    }

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        // Il checkTree posta `visible_event_ids[]` (più un hidden vuoto):
        // filtriamo i non-id e persistiamo un array JSON di interi.
        $raw = $values['visible_event_ids'] ?? [];

        if (is_string($raw)) {
            $raw = rsvpDecodeJsonArray($raw);
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $raw),
            static fn (int $id): bool => $id > 0
        )));

        $values['visible_event_ids'] = json_encode($ids);

        return $values;
    }

    public static function mutateFormValues(
        array $values,
        string $mode,
        string $context = 'backend'
    ): array {
        // Il pre-check del checkTree confronta in modo stretto con le chiavi
        // (int) delle opzioni: servono interi, non stringhe.
        $values['visible_event_ids'] = array_values(array_filter(
            array_map('intval', rsvpDecodeJsonArray($values['visible_event_ids'] ?? '[]')),
            static fn (int $id): bool => $id > 0
        ));

        return $values;
    }

    private static function eventOptions(): array
    {
        try {
            $events = Event::all();
        } catch (\Throwable) {
            return [];
        }

        $options = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $id = (int) ($event['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $name = trim((string) ($event['name'] ?? ''));
            $code = trim((string) ($event['code'] ?? ''));
            $options[$id] = [
                'label' => $name !== '' ? $name : $code,
                'position' => (int) ($event['position'] ?? 0),
            ];
        }

        uasort($options, static function (array $left, array $right): int {
            return ($left['position'] <=> $right['position'])
                ?: strcmp($left['label'], $right['label']);
        });

        return array_map(static fn (array $option): string => $option['label'], $options);
    }
}
