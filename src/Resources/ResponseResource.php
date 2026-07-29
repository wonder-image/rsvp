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
use Wonder\Http\Route;
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
            'company' => 'Azienda',
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
        // I campi configurabili (azienda + partecipante età/sesso/allergie)
        // seguono il mode dell'autorizzazione attiva (Nascosto/Facoltativo/
        // Obbligatorio). In backend, senza autorizzazione, valgono i default:
        // vengono mostrati tutti.
        $modes = self::fieldModes();

        $company = self::modeField(
            FormField::key('company')->text()
                ->autocomplete('organization')
                ->label(__t('components.forms.fields.company.label')),
            $modes['company']
        );

        $age = self::modeField(
            FormField::key('participants[__INDEX__][age]')->number()->decimal(0)
                ->label(__t('components.forms.fields.age.label')),
            $modes['age']
        );

        $sex = self::modeField(
            FormField::key('participants[__INDEX__][sex]')->select(
                ['' => __t('components.forms.fields.sex.placeholder')] + rsvpSexDictionary()
            )->label(__t('components.forms.fields.sex.label')),
            $modes['sex']
        );

        $dietary = self::modeField(
            FormField::key('participants[__INDEX__][dietary_requirements]')->textarea()
                ->label(__t('components.forms.fields.dietary_requirements.label')),
            $modes['allergies']
        );

        $schema = [
            // Il codice invito è obbligatorio solo quando lo richiede il
            // contesto RSVP; `assertSubmission()` applica quel vincolo.
            FormField::key('invite_code_id')->hidden(),
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
        ];

        if ($company !== null) {
            $schema[] = $company;
        }

        /**
         * Dati di contatto altri partecipanti.
         */
        $schema[] = FormField::key('participants_count')->select()
            ->value('1')->required()
            ->label(__t('components.forms.fields.participants_count.label'));

        $schema[] = FormField::key('participants[__INDEX__][name]')->text()->required()
            ->label(__t('components.forms.fields.name.label'));

        $schema[] = FormField::key('participants[__INDEX__][surname]')->text()->required()
            ->label(__t('components.forms.fields.surname.label'));

        foreach ([$age, $sex, $dietary] as $participantField) {
            if ($participantField !== null) {
                $schema[] = $participantField;
            }
        }

        $schema[] = FormField::key('participants[__INDEX__][is_child]')->checkbox()
            ->label(__t('components.forms.fields.is_child.label'));

        /**
         * Dati consensi.
         */
        $schema[] = FormField::key('accept_privacy_policy')->acceptDocument('privacy_policy')->required();
        $schema[] = FormField::key('accept_image_release')->acceptDocument('image_release')->required();

        // Conferma partecipazione: sempre presente nello schema (la gestione
        // backend è sempre disponibile); il frontend la mostra solo se
        // l'autorizzazione attiva la abilita (`$state['enable_attendance_status']`).
        array_splice($schema, 3, 0, [
            FormField::key('attendance_status')->radio([
                    'confirmed' => __t('pages.rsvp.form.attendance_confirmed_label'),
                    'declined' => __t('pages.rsvp.form.attendance_declined_label'),
                ])
                ->label(__t('components.forms.fields.attendance_status.label'))
                ->required()->value('confirmed'),
        ]);

        return $schema;
    }

    /**
     * Mode dei campi configurabili dall'autorizzazione attiva.
     *
     * @return array<string, string>
     */
    private static function fieldModes(): array
    {
        $state = Rsvp::context();

        return is_array($state['field_modes'] ?? null)
            ? $state['field_modes']
            : rsvpFieldModes([]);
    }

    /**
     * Applica il mode a un campo: `hidden` → escluso (null), `required` →
     * obbligatorio, `optional` → invariato.
     */
    private static function modeField(FormField $field, string $mode): ?FormField
    {
        if ($mode === 'hidden') {
            return null;
        }

        if ($mode === 'required') {
            $field->required();
        }

        return $field;
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
            ->downloadFileName('RSVP-'.date('Y-m-d-H-i-s'));
    }

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
            [
                'label' => 'Nome',
                'value' => static fn (array $r): string => (string) (
                    $r['export_participant_name'] ?? ($r['contact_name'] ?? '')
                ),
            ],
            [
                'label' => 'Cognome',
                'value' => static fn (array $r): string => (string) (
                    $r['export_participant_surname'] ?? ($r['contact_surname'] ?? '')
                ),
            ],
            [
                'label' => 'Età',
                'value' => static fn (array $r): string => (string) ($r['export_participant_age'] ?? ''),
            ],
            [
                'label' => 'Sesso',
                'value' => static fn (array $r): string => (string) ($r['export_participant_sex'] ?? ''),
            ],
            [
                'label' => 'Tipo',
                'value' => static fn (array $r): string => (string) ($r['export_participant_type'] ?? ''),
            ],
            [
                'label' => 'Esigenze alimentari',
                'value' => static fn (array $r): string => (string) ($r['export_participant_dietary_requirements'] ?? ''),
            ],
            ['label' => 'Azienda', 'value' => 'company'],
            ['label' => 'Email referente', 'value' => 'contact_email'],
            ['label' => 'Telefono referente', 'value' => 'contact_phone'],
            ['label' => 'Partecipanti prenotazione', 'value' => 'participants_count'],
            ['label' => 'Bambini prenotazione', 'value' => 'children_count'],
            ['label' => 'Privacy', 'value' => 'pretty_accept_privacy_policy'],
            ['label' => 'Foto', 'value' => 'pretty_accept_image_release']
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

    /**
     * Sostituisce la destinazione del pulsante export standard con il
     * downloader RSVP, che espande ogni risposta in una riga per partecipante.
     */
    public static function registerBackendRoutes(string $rootApp, string $slug): void
    {
        $backendPermissions = (array) static::permissionSchema()->get('backend');

        Route::get('/participants-export/{format}/', Rsvp::handlerPath('backend/response/export.php'), [
            'resource' => $slug,
            'resource_action' => 'export',
        ])->name('export')
            ->permit((array) ($backendPermissions['list'] ?? []))
            ->where('format', '(csv|xlsx)');
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

        $state = Rsvp::context();
        $normalized = SubmissionNormalizer::fromPayload(
            $payload,
            !empty($state['enable_attendance_status'])
        );
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

        // Azienda obbligatoria solo se l'autorizzazione la marca "required".
        $fieldModes = is_array($state['field_modes'] ?? null) ? $state['field_modes'] : rsvpFieldModes([]);

        if (($fieldModes['company'] ?? 'required') === 'required') {
            $requireField(
                (string) ($normalized['company'] ?? ''),
                __t('components.forms.fields.company.label')
            );
        }

        // Verifica compilazione duplicata (flag nelle Impostazioni RSVP): una
        // sola risposta per email o per telefono già presenti a sistema.
        $settings = is_array($state['settings'] ?? null) ? $state['settings'] : [];

        if (rsvpDuplicateCheckEnabled($settings)) {
            $alreadySubmitted = static function (string $column, string $value): bool {
                if ($value === '') {
                    return false;
                }

                $row = Response::find([$column => $value, 'deleted' => 'false'], 1);

                return is_array($row) && $row !== [];
            };

            $email = strtolower(trim((string) ($normalized['contact_email'] ?? '')));
            $phone = trim((string) ($normalized['contact_phone'] ?? ''));

            if ($alreadySubmitted('contact_email', $email) || $alreadySubmitted('contact_phone', $phone)) {
                $fail(
                    'pages.rsvp.api.submit.duplicate_submission',
                    'Risulta già una compilazione con questa email o questo telefono.'
                );
            }
        }

        if ($attendanceStatus === 'confirmed' && (int) ($normalized['participants_count'] ?? 0) <= 0) {
            $fail('pages.rsvp.api.submit.missing_participants', 'Partecipanti mancanti.');
        }

        // `max_participants` è il limite dei soli adulti; i bambini hanno il
        // proprio limite. Specchia l'enforcement del form frontend.
        if ($attendanceStatus === 'confirmed') {
            $maxAdults = max(1, (int) ($state['max_participants'] ?? 1));
            $maxChildren = max(0, (int) ($state['max_children'] ?? 0));
            $childrenCount = (int) ($normalized['children_count'] ?? 0);
            $adultsCount = max(0, (int) ($normalized['participants_count'] ?? 0) - $childrenCount);

            if ($childrenCount > $maxChildren) {
                $fail(
                    'pages.rsvp.api.submit.max_children_exceeded',
                    'Numero massimo di bambini superato: puoi indicarne al massimo {{max}}.',
                    ['max' => $maxChildren]
                );
            }

            if ($adultsCount > $maxAdults) {
                $fail(
                    'pages.rsvp.api.submit.max_adults_exceeded',
                    'Numero massimo di adulti superato: puoi indicarne al massimo {{max}}.',
                    ['max' => $maxAdults]
                );
            }
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

    /**
     * Backend: la gestione conferma partecipazione è SEMPRE disponibile
     * (la conferma è ora per-autorizzazione e ogni risposta porta comunque il
     * proprio `attendance_status`). Il gating lato frontend avviene invece per
     * autorizzazione via `$state['enable_attendance_status']`.
     */
    private static function attendanceStatusEnabled(): bool
    {
        return true;
    }
}
