<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PageSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;
use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Rsvp;

final class ResponseResource extends Resource
{
    public static string $model = Response::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'risposta RSVP',
            'plural_label' => 'risposte RSVP',
            'last' => 'ultime',
            'all' => 'tutte',
            'article' => 'le',
            'full' => 'usata',
            'empty' => 'vuota',
            'this' => 'questa',
        ];
    }

    public static function labelSchema(): array
    {
        $labels = [
            'booking_code' => 'Codice prenotazione',
            'contact_name' => 'Nome',
            'contact_surname' => 'Cognome',
            'contact_email' => 'Email',
            'contact_phone' => 'Telefono',
            'participants_count' => 'Partecipanti',
            'children_count' => 'Bambini',
            'event_key' => 'Evento',
            'invite_code' => 'Codice invito',
            'invite_group_code' => 'Gruppo invito',
            'authorization_code' => 'Autorizzazione',
            'locale' => 'Lingua',
            'notes' => 'Richieste',
            'source_url' => 'URL origine',
        ];

        foreach (Response::customFieldDefinitions() as $field) {
            $labels[$field['column']] = (string) $field['label'];
        }

        return $labels;
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('booking_code')->text()->size('little')->link('view'),
            TableColumn::key('contact_name')->text()->columns(['contact_name', 'contact_surname']),
            TableColumn::key('contact_email')->text()->link('mailto'),
            TableColumn::key('participants_count')->text()->size('little'),
            TableColumn::key('event_key')->text()->size('little'),
            TableColumn::key('creation')->datetime()->sortable(),
            TableColumn::key('actions')->button()->actions(['view', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Risposte RSVP')
            ->hideButtonAdd()
            ->results()
            ->filters()
            ->searchFields(['contact_name', 'contact_surname', 'contact_email', 'contact_phone', 'event_key', 'invite_code', 'authorization_code']);
    }

    public static function pageSchema(): PageSchema
    {
        return PageSchema::for(static::class)
            ->only(['list', 'view', 'delete'])
            ->view('list', Rsvp::viewPath('backend/response/list.php'))
            ->view('show', Rsvp::viewPath('backend/response/show.php'))
            ->title('view', 'Dettaglio risposta RSVP');
    }

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)->enabled(false);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backend(['list', 'view'], ['admin', 'rsvp_response_viewer'])
            ->backend('delete', ['admin']);
    }

    public static function navigationSchema(): NavigationSchema
    {
        return NavigationSchema::for(static::class)
            ->section('RSVP', 'rsvp', 'bi-ticket-perforated')
            ->title('Risposte')
            ->order(10)
            ->authority(['admin', 'rsvp_response_viewer']);
    }

    public static function registerBackendRoutes(string $rootApp, string $slug): void
    {
        Route::get('/export/{format}/', Rsvp::handlerPath('backend/response-export.php'), [
            'resource' => $slug,
            'resource_action' => 'export',
        ])->name('export')
            ->permit(['admin', 'rsvp_response_viewer'])
            ->where('format', '(csv|xls)');
    }
}
