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
use Wonder\Http\Route;
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\SubmissionNotifier;

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
            'attendance_status' => 'Conferma',
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

    public static function formSchema(): array
    {
        if (!self::attendanceStatusEnabled()) {
            return [];
        }

        return [
            FormInput::key('attendance_status')->select([
                'confirmed' => 'Confermato',
                'declined' => 'Declinato',
            ])->required()->value('confirmed'),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        if (!self::attendanceStatusEnabled()) {
            return null;
        }

        return (new Form)->components([
            (new Card)->components([
                static::getInput('attendance_status')->columnSpan(12),
            ])->columns(12)->columnSpan(12),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        $columns = [
            TableColumn::key('booking_code')->text()->size('little')->link('view'),
            TableColumn::key('contact_name')->text()->columns(['contact_name', 'contact_surname']),
            TableColumn::key('contact_email')->text()->link('mailto'),
            TableColumn::key('event_key')->text(),
            TableColumn::key('participants_count')->text()->size('little'),
            TableColumn::key('creation')->datetime()->sortable(),
        ];

        if (self::attendanceStatusEnabled()) {
            array_splice($columns, 5, 0, [
                TableColumn::key('attendance_status')->badge()->function('rsvpResponseAttendanceStatus', 'attendance_status', 'automaticResize')->size('little'),
            ]);
        }

        $columns[] = TableColumn::key('actions')->button()->actions(
            self::attendanceStatusEnabled() ? ['view', 'edit', 'delete'] : ['view', 'delete']
        );

        return $columns;
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
            ->only(self::attendanceStatusEnabled() ? ['list', 'view', 'edit', 'update', 'delete'] : ['list', 'view', 'delete'])
            ->view('list', Rsvp::viewPath('backend/response/list.php'))
            ->view('show', Rsvp::viewPath('backend/response/show.php'))
            ->title('view', 'Dettaglio risposta RSVP')
            ->title('edit', 'Modifica stato RSVP');
    }

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)->enabled(false);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backend(['list', 'view'], ['admin', 'rsvp_response_viewer'])
            ->backend(['edit', 'update'], ['admin'])
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

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        if (!self::attendanceStatusEnabled()) {
            unset($values['attendance_status']);

            return $values;
        }

        $values['attendance_status'] = rsvpAttendanceStatusValue($values['attendance_status'] ?? null);

        return $values;
    }

    public static function mutateFormValues(
        array $values,
        string $mode,
        string $context = 'backend'
    ): array {
        if (!self::attendanceStatusEnabled()) {
            return $values;
        }

        $values['attendance_status'] = rsvpAttendanceStatusValue($values['attendance_status'] ?? null);

        return $values;
    }

    private static function attendanceStatusEnabled(): bool
    {
        return rsvpAttendanceStatusEnabled(SubmissionNotifier::settings());
    }
}
