<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\Api\EndpointException;
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
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Services\InviteCodeSession;
use Wonder\Plugin\Rsvp\Services\SubmissionNormalizer;
use Wonder\Plugin\Rsvp\Services\SubmissionNotifier;
use Wonder\Plugin\Rsvp\Support\ExtensionRegistry;

/**
 * Resource unica delle risposte RSVP.
 *
 * - Backend: lista, dettaglio, modifica stato (solo attendance), export.
 * - Frontend: rotta di submission `api.resource.rsvp-responses.store`. Il form
 *   è renderizzato da `view/components/form.php`; normalizzazione, validazione
 *   e notifiche vivono in `mutateRequestValues()` / `afterStore()`.
 */
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
            'request_url' => 'URL origine',
        ];

        foreach (Response::customFieldDefinitions() as $field) {
            $labels[$field['column']] = (string) $field['label'];
        }

        return $labels;
    }

    /**
     * Sorgente canonica degli input usati dal form RSVP frontend.
     *
     * Quando l'attendance status è attivo, lo stesso schema viene riusato
     * anche dal backend per la pagina di modifica stato.
     */
    public static function formSchema(): array
    {
        $schema = [
            FormField::key('invite_code_id')->hidden()->required(),
            FormField::key('locale')->hidden()->required(),
            FormField::key('request_url')->hidden(),
            FormField::key('event_key')->radio()->required(),

            /**
             * Dati di contatto principali.
             */
            FormField::key('contact_name')->text()
                ->autocomplete()
                ->label(__t('components.forms.fields.contact_name.label')),

            FormField::key('contact_surname')->text()
                ->autocomplete()
                ->label(__t('components.forms.fields.contact_surname.label')),

            FormField::key('contact_phone')->phone()->required()
                ->autocomplete()
                ->label(__t('components.forms.fields.contact_phone.label')),

            FormField::key('contact_email')->email()->required()
                ->autocomplete()
                ->label(__t('components.forms.fields.contact_email.label')),

            /**
             * Dati di contatto altri partecipanti.
             */
            FormField::key('participants_count')->select()
                ->value('1')->required()
                ->label(__t('components.forms.fields.participants_count.label')),

            FormField::key('participants[__INDEX__][name]')->text()->required()
                ->label(__t('components.forms.fields.name.label')),

            FormField::key('participants[__INDEX__][surname]')->text()->required()
                ->label(__t('components.forms.fields.surname.label')),

            FormField::key('participants[__INDEX__][dietary_requirements]')->textarea()
                ->label(__t('components.forms.fields.dietary_requirements.label')),

            FormField::key('participants[__INDEX__][is_child]')->checkbox()
                ->label(__t('components.forms.fields.is_child.label')),

            /**
             * Dati consensi.
             */
            FormField::key('accept_privacy_policy')->acceptDocument('privacy_policy')->required(),
            FormField::key('accept_image_release')->acceptDocument('image_release')->required()
            
        ];

        if (self::attendanceStatusEnabled()) {
            array_splice($schema, 3, 0, [
                FormField::key('attendance_status')->radio([
                        'confirmed' => __t('pages.rsvp.form.attendance_confirmed_label'),
                        'declined' => __t('pages.rsvp.form.attendance_declined_label'),
                    ])
                    ->label(__t('components.forms.fields.attendance_status.label'))
                    ->required()->value('confirmed'),
            ]);
        }

        return $schema;
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

        $columns[] = TableColumn::key('actions')->button()->actions(['view', 'delete']);

        return $columns;
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Risposte RSVP')
            ->hideButtonAdd()
            ->results()
            ->filters()
            ->searchFields(['contact_name', 'contact_surname', 'contact_email', 'contact_phone', 'event_key', 'invite_code', 'authorization_code'])
            ->download(['xlsx', 'csv'])
            ->downloadColumns(self::downloadColumnsSchema())
            ->downloadFileName('RSVP-'.date('Y-m-d'));
    }

    /**
     * Colonne dell'export tabella (una riga per risposta).
     *
     * @return array<int, array{label:string, value:string|callable}>
     */
    private static function downloadColumnsSchema(): array
    {
        $columns = [
            ['label' => 'Codice prenotazione', 'value' => 'booking_code'],
            ['label' => 'Creato il', 'value' => 'creation'],
        ];

        if (self::attendanceStatusEnabled()) {
            $columns[] = ['label' => 'Conferma', 'value' => 'pretty_attendance_status'];
        }

        $columns = array_merge($columns, [
            ['label' => 'Nome', 'value' => 'contact_name'],
            ['label' => 'Cognome', 'value' => 'contact_surname'],
            ['label' => 'Email', 'value' => 'contact_email'],
            ['label' => 'Telefono', 'value' => 'contact_phone'],
            ['label' => 'Partecipanti', 'value' => 'participants_count'],
            ['label' => 'Bambini', 'value' => 'children_count'],
            ['label' => 'Elenco partecipanti', 'value' => static fn (array $r): string => self::participantsSummary($r)],
            ['label' => 'Eventi selezionati', 'value' => static fn (array $r): string => self::eventsSummary($r)],
            ['label' => 'Codice invito', 'value' => 'invite_code'],
            ['label' => 'Gruppo invito', 'value' => 'invite_group_code'],
            ['label' => 'Autorizzazione', 'value' => 'authorization_code'],
            ['label' => 'Richieste', 'value' => 'notes'],
            ['label' => 'Privacy', 'value' => 'pretty_accept_privacy_policy'],
            ['label' => 'Foto', 'value' => 'pretty_accept_image_release'],
            ['label' => 'Lingua', 'value' => 'locale'],
            ['label' => 'URL origine', 'value' => 'request_url'],
        ]);

        foreach (Response::customFieldDefinitions() as $field) {
            $columns[] = [
                'label' => rsvpCustomFieldLabel($field, (string) ($field['key'] ?? '')),
                'value' => static fn (array $r): string => rsvpRenderCustomFieldValue($field, $r[$field['column']] ?? null),
            ];
        }

        return $columns;
    }

    private static function participantsSummary(array $row): string
    {
        $labels = [];

        foreach (rsvpDecodeJsonArray($row['participants'] ?? '[]') as $participant) {
            if (!is_array($participant)) {
                continue;
            }

            $name = trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? '')));

            if ($name === '') {
                continue;
            }

            if (!empty($participant['is_child'])) {
                $name .= ' (bambino)';
            }

            $labels[] = $name;
        }

        return implode(', ', $labels);
    }

    private static function eventsSummary(array $row): string
    {
        $events = rsvpDecodeJsonArray($row['events'] ?? '[]');

        if ($events !== []) {
            return implode(', ', array_map('strval', $events));
        }

        return (string) ($row['event_key'] ?? '');
    }

    public static function pageSchema(): PageSchema
    {
        return PageSchema::for(static::class)
            ->only(self::attendanceStatusEnabled() ? ['list', 'view', 'edit', 'update', 'delete'] : ['list', 'view', 'delete'])
            ->view('show', Rsvp::viewPath('pages/backend/response/show.php'))
            ->title('view', 'Dettaglio risposta RSVP')
            ->title('edit', 'Modifica stato RSVP');
    }

    public static function apiSchema(): ApiSchema
    {
        // Solo `store`: è l'endpoint di submission del form frontend.
        return ApiSchema::for(static::class)->only(['store']);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backend(['list', 'view'], ['admin', 'rsvp_response_viewer'])
            ->backend(['edit', 'update'], ['admin'])
            ->backend('delete', ['admin'])
            ->api('store', ['api_internal_user', 'api_public_access']);
    }

    public static function navigationSchema(): NavigationSchema
    {
        return NavigationSchema::for(static::class)
            ->title('Risposte')
            ->sectionOrder(610)
            ->authority([ 'admin', 'rsvp_response_viewer' ]);
    }

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        // Submission frontend: normalizza il POST grezzo nelle colonne della
        // risposta (il whitelist `writableValues` non gestisce participants[],
        // custom_fields{}, accept_<doc>).
        if ($context === 'api' && $action === 'store') {
            return self::mutateSubmission();
        }

        // Backend: modifica del solo stato di partecipazione.
        if (!self::attendanceStatusEnabled()) {
            unset($values['attendance_status']);

            return $values;
        }

        $values['attendance_status'] = rsvpAttendanceStatusValue($values['attendance_status'] ?? null);

        return $values;
    }

    public static function afterStore(object $result, array $values = []): void
    {
        $responseId = (int) ($result->insert_id ?? 0);

        if ($responseId <= 0) {
            return;
        }

        SubmissionNotifier::notify($values, $responseId);

        $values['id'] = $responseId;
        ExtensionRegistry::get()->afterSubmit($values, $_POST);
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

    /**
     * Normalizza e valida la submission frontend, restituendo le colonne
     * della risposta. Legge il POST grezzo (payload dinamico).
     *
     * @return array<string, mixed>
     */
    private static function mutateSubmission(): array
    {
        $payload = $_POST;

        if (isset($payload['g-recaptcha-token'])) {
            verifyRecaptcha($payload);

            if (!empty($GLOBALS['ALERT'])) {
                throw new EndpointException(
                    __t('notifications.'.$GLOBALS['ALERT'].'.text'),
                    422
                );
            }
        }

        $normalized = SubmissionNormalizer::fromPayload($payload);
        $state = Rsvp::context();
        $attendanceStatus = rsvpAttendanceStatusValue($normalized['attendance_status'] ?? null);

        self::assertSubmission($normalized, $attendanceStatus, $state, $payload);

        // Preserva le colonne custom field prima dell'hook beforeSubmit.
        $resolvedCustomColumns = [];

        foreach (Response::customFieldColumns() as $column) {
            if (array_key_exists($column, $normalized)) {
                $resolvedCustomColumns[$column] = $normalized[$column];
            }
        }

        $normalized = ExtensionRegistry::get()->beforeSubmit($normalized);

        return array_replace($resolvedCustomColumns, $normalized);
    }

    /**
     * Validazione RSVP-specifica: lancia EndpointException(422) così la
     * pipeline API la restituisce al frontend.
     *
     * @param array<string, mixed> $normalized
     * @param array<string, mixed> $state
     * @param array<string, mixed> $payload
     */
    private static function assertSubmission(
        array $normalized,
        string $attendanceStatus,
        array $state,
        array $payload
    ): void {
        $fail = static function (string $key, string $fallback, array $replacements = []): never {
            throw new EndpointException(__t($key, $replacements), 422);
        };

        $requireField = static function (string $value, string $label) use ($fail): void {
            if (trim($value) === '') {
                $fail(
                    'pages.rsvp.api.submit.required_field_missing',
                    'Campo obbligatorio mancante: {{field}}.',
                    ['field' => $label]
                );
            }
        };

        if (trim((string) ($normalized['contact_email'] ?? '')) === '') {
            $fail('pages.rsvp.api.submit.missing_email', 'Email mancante.');
        }

        if ($attendanceStatus === 'confirmed' && (int) ($normalized['participants_count'] ?? 0) <= 0) {
            $fail('pages.rsvp.api.submit.missing_participants', 'Partecipanti mancanti.');
        }

        if ($attendanceStatus === 'declined') {
            $requireField((string) ($normalized['contact_name'] ?? ''), __t('components.forms.fields.contact_name.label'));
            $requireField((string) ($normalized['contact_surname'] ?? ''), __t('components.forms.fields.contact_surname.label'));
            $requireField((string) ($normalized['contact_phone'] ?? ''), __t('components.forms.fields.contact_phone.label'));
        }

        $session = InviteCodeSession::current();

        if (($state['requires_invite_code'] ?? false) && ($session['id'] ?? 0) <= 0) {
            $fail('pages.rsvp.api.submit.invite_code_required', 'Questo RSVP richiede un codice invito valido.');
        }

        if (($session['id'] ?? 0) > 0 && !($session['can_submit'] ?? true)) {
            $fail('pages.rsvp.api.submit.invite_code_exhausted', 'Il codice invito ha già esaurito gli invii disponibili.');
        }

        $consents = SubmissionNormalizer::consents($normalized);

        if (empty($consents['privacy'])) {
            $fail('pages.rsvp.api.submit.privacy_required', 'È necessario accettare la privacy.');
        }

        if (
            $attendanceStatus === 'confirmed'
            && ($state['require_image_release'] ?? false)
            && empty($consents['photo'])
        ) {
            $fail('pages.rsvp.api.submit.image_release_required', 'È necessario accettare la liberatoria immagini.');
        }

        // `custom_fields` nello stato sono FormField pronti al render.
        $customFieldInputs = is_array($state['custom_fields'] ?? null) ? $state['custom_fields'] : [];
        $customFieldsPayload = is_array($payload['custom_fields'] ?? null) ? $payload['custom_fields'] : [];

        if ($attendanceStatus === 'declined') {
            return;
        }

        foreach ($customFieldInputs as $field) {
            if (!$field instanceof FormField) {
                continue;
            }

            $required = str_contains((string) ($field->get('attribute') ?? ''), 'required');

            if (!$required) {
                continue;
            }

            $key = (string) $field->name;

            // I custom field sono resi con name=<key> (top-level): accettiamo
            // sia il blocco nested `custom_fields` sia la chiave diretta.
            $value = $customFieldsPayload[$key] ?? ($payload[$key] ?? null);
            $missing = $value === null
                || (is_string($value) && trim($value) === '')
                || (is_array($value) && $value === []);

            if ($missing) {
                $fail(
                    'pages.rsvp.api.submit.required_field_missing',
                    'Campo obbligatorio mancante: {{field}}.',
                    ['field' => (string) ($field->get('label') ?: $key)]
                );
            }
        }
    }

    private static function attendanceStatusEnabled(): bool
    {
        return rsvpAttendanceStatusEnabled(SubmissionNotifier::settings());
    }
}
