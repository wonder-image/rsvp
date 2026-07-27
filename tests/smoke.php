<?php

declare(strict_types=1);

namespace {
    if (!function_exists('__t')) {
        function __t(string $key, array $replacements = []): string
        {
            $messages = [
                'pages.rsvp.common.confirmed' => 'Confermato',
                'pages.rsvp.common.declined' => 'Non partecipo',
                'pages.rsvp.common.accepted' => 'Accettato',
                'pages.rsvp.common.rejected' => 'Rifiutato',
                'legal.privacy_policy.label' => 'Privacy policy',
                'legal.image_release.label' => 'Liberatoria immagini',
                'components.forms.fields.sex.options.male' => 'Uomo',
                'components.forms.fields.sex.options.female' => 'Donna',
                'components.forms.fields.sex.options.other' => 'Altro',
            ];

            $message = $messages[$key] ?? $key;

            foreach ($replacements as $placeholder => $value) {
                $message = str_replace('{{'.$placeholder.'}}', (string) $value, $message);
            }

            return $message;
        }
    }

    if (!function_exists('__l')) {
        function __l(): string
        {
            return 'it';
        }
    }

    if (!function_exists('arrayDetails')) {
        function arrayDetails(array $items, mixed $key = null): array|object
        {
            if (!is_scalar($key)) {
                return (object) ['text' => ''];
            }

            $normalizedKey = trim((string) $key);

            if ($normalizedKey === '' || !isset($items[$normalizedKey]) || !is_array($items[$normalizedKey])) {
                return (object) ['text' => ''];
            }

            return (object) $items[$normalizedKey];
        }
    }
}

namespace Wonder\Plugin\Rsvp\Models {
    final class Authorization
    {
        public static function find(array $where, int $limit = 1): array
        {
            return ((int) ($where['id'] ?? 0) === 7)
                ? ['id' => 7, 'code' => 'vip']
                : [];
        }
    }

    final class InviteCode
    {
        public static function find(array $where, int $limit = 1): array
        {
            return ((int) ($where['id'] ?? 0) === 42)
                ? ['id' => 42, 'invite_group_id' => 3, 'authorization_id' => 7]
                : [];
        }
    }

    final class InviteGroup
    {
        public static function find(array $where, int $limit = 1): array
        {
            return ((int) ($where['id'] ?? 0) === 3)
                ? ['id' => 3, 'code' => 'family']
                : [];
        }
    }

    final class Response
    {
        /**
         * @return array<string, array{key:string,label:string,type:string,column:string,options:array<string,string>,required:bool,value:mixed}>
         */
        public static function customFieldDefinitions(): array
        {
            return [
                'meal' => [
                    'key' => 'meal',
                    'label' => 'Pasto',
                    'type' => 'select',
                    'column' => 'meta_meal',
                    'options' => ['veg' => 'Vegetariano'],
                    'required' => false,
                    'value' => '',
                ],
            ];
        }
    }
}

namespace Wonder\Plugin\Rsvp\Services {
    final class InviteCodeSession
    {
        public static function current(): array
        {
            return [
                'id' => 42,
                'code' => 'ABCD',
                'invite_group_code' => 'family',
                'authorization_code' => 'vip',
            ];
        }
    }

    final class SubmissionNotifier
    {
        public static function settings(): array
        {
            return [
                'enable_attendance_status' => 'true',
            ];
        }
    }
}

namespace {
    require __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../src/Services/SubmissionNormalizer.php';

    use Wonder\Http\Route;
    use Wonder\Plugin\Rsvp\Rsvp;
    use Wonder\Plugin\Rsvp\Resources\ResponseResource;
    use Wonder\Plugin\Rsvp\Services\ResponseExporter;
    use Wonder\Plugin\Rsvp\Services\SubmissionNormalizer;

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message."\nExpected: ".var_export($expected, true)."\nActual: ".var_export($actual, true));
        }
    }

    function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    $assertions = 0;

    $assert = static function (callable $callback) use (&$assertions): void {
        $callback();
        $assertions++;
    };

    try {
        $assert(static fn () => assertSame('declined', rsvpAttendanceStatusValue('NO'), 'Normalizza correttamente lo stato declined.'));
        $assert(static fn () => assertSame('confirmed', rsvpAttendanceStatusValue('yes'), 'Normalizza correttamente lo stato confirmed.'));
        $assert(static fn () => assertTrue(rsvpAttendanceStatusEnabled(['enable_attendance_status' => 'on']), 'Riconosce il flag attendance attivo.'));
        $assert(static fn () => assertSame(['alpha' => 1], rsvpDecodeJsonArray('{"alpha":1}'), 'Decodifica JSON object in array associativo.'));
        $assert(static fn () => assertSame('--', rsvpJsonPrettyList('[]'), 'Rende placeholder per liste JSON vuote.'));
        $assert(static fn () => assertSame(['uno', 'due'], rsvpParseListText("uno;\ndue\nuno"), 'Parsa liste testuali deduplicate.'));
        $assert(static fn () => assertSame('meta_guest_name', rsvpCustomFieldColumn(' Guest Name '), 'Normalizza il nome colonna dei custom field.'));
        $assert(static fn () => assertTrue(Rsvp::canAccessForm([
            'requires_invite_code' => false,
            'session' => [],
        ]), 'Il form pubblico resta accessibile senza sessione invito.'));
        $assert(static fn () => assertTrue(!Rsvp::canAccessForm([
            'requires_invite_code' => true,
            'session' => [],
        ]), 'Il form protetto non è accessibile senza sessione invito.'));
        $assert(static fn () => assertTrue(Rsvp::canAccessForm([
            'requires_invite_code' => true,
            'session' => [
                'id' => 42,
                'usage_mode' => 'single_use',
                'usage_count' => 0,
                'can_submit' => true,
            ],
        ]), 'Un codice monouso non utilizzato consente di accedere al form.'));
        $assert(static fn () => assertTrue(!Rsvp::canAccessForm([
            'requires_invite_code' => true,
            'session' => [
                'id' => 42,
                'usage_mode' => 'single_use',
                'usage_count' => 1,
                'can_submit' => false,
            ],
        ]), 'Un codice monouso già utilizzato non consente di accedere al form.'));
        $assert(static fn () => assertTrue(Rsvp::canAccessForm([
            'requires_invite_code' => true,
            'session' => [
                'id' => 42,
                'usage_mode' => 'multiple_use',
                'usage_count' => 1,
                'can_submit' => true,
            ],
        ]), 'Un codice multiuso resta accessibile dopo un invio.'));
        $assert(static fn () => assertSame('Vegetariano', rsvpRenderCustomFieldValue([
            'type' => 'select',
            'options' => ['veg' => 'Vegetariano'],
        ], 'veg'), 'Renderizza i valori select usando le label configurate.'));
        $assert(static fn () => assertSame([
            'title' => 'Ciao',
            'cta' => 'Vai',
        ], rsvpResolveLocalizedValue([
            'title' => ['it' => 'Ciao', 'en' => 'Hello'],
            'cta' => ['default' => 'Vai'],
        ], 'it'), 'Risoluzione localizzata coerente per strutture annidate.'));

        $normalized = SubmissionNormalizer::fromPayload([
            'attendance_status' => 'confirmed',
            'invite_code_id' => 42,
            'contact_phone' => '+39 123456',
            'contact_email' => 'USER@EXAMPLE.COM',
            'company' => '  Acme S.p.A.  ',
            'participants' => [
                [
                    'name' => 'Mario',
                    'surname' => 'Rossi',
                    'age' => '30',
                    'sex' => 'male',
                    'dietary_requirements' => 'No glutine',
                    'is_child' => 'false',
                ],
                [
                    'name' => 'Luca',
                    'surname' => 'Rossi',
                    'age' => '8',
                    'sex' => 'other',
                    'dietary_requirements' => '',
                    'is_child' => 'true',
                ],
            ],
            'events' => ['ceremony', 'party', 'ceremony'],
            'accept_privacy_policy' => 'on',
            'accept_image_release' => '0',
            'custom_fields' => ['meal' => 'veg'],
            'status' => 'confirmed',
            'unknown_flag' => 'keep-me',
        ]);

        $normalizedParticipants = SubmissionNormalizer::participantsFromNormalized($normalized);

        $assert(static fn () => assertSame('Acme S.p.A.', $normalized['company'], 'Normalizza il campo azienda a livello di prenotazione.'));
        $assert(static fn () => assertSame(30, $normalizedParticipants[0]['age'], 'Preserva l’età del partecipante come intero.'));
        $assert(static fn () => assertSame('male', $normalizedParticipants[0]['sex'], 'Preserva il sesso del partecipante.'));
        $assert(static fn () => assertSame('other', $normalizedParticipants[1]['sex'], 'Preserva il sesso del secondo partecipante.'));

        $assert(static fn () => assertSame('Mario', $normalized['contact_name'], 'Deriva il contatto principale dal primo partecipante quando manca il campo dedicato.'));
        $assert(static fn () => assertSame('user@example.com', $normalized['contact_email'], 'Normalizza l’email in lowercase.'));
        $assert(static fn () => assertSame(2, $normalized['participants_count'], 'Conta correttamente i partecipanti.'));
        $assert(static fn () => assertSame(1, $normalized['children_count'], 'Conta correttamente i bambini.'));
        $assert(static fn () => assertSame('true', $normalized['accept_privacy_policy'], 'Allinea il consenso privacy con la colonna dedicata.'));
        $assert(static fn () => assertSame('false', $normalized['accept_image_release'], 'Allinea il consenso foto con la colonna dedicata.'));
        $assert(static fn () => assertSame('veg', $normalized['meta_meal'], 'Materializza i custom field sulle colonne meta_*.'));
        $assert(static fn () => assertSame([
            'privacy' => true,
            'photo' => false,
        ], SubmissionNormalizer::consents($normalized), 'Serializza i consensi nel payload JSON.'));
        $assert(static fn () => assertSame([
            'privacy_policy' => ['accepted' => true, 'document_id' => 0],
            'image_release' => ['accepted' => false, 'document_id' => 0],
        ], SubmissionNormalizer::legalDocumentsFromNormalized($normalized), 'Serializza i documenti legali normalizzati.'));
        $assert(static fn () => assertSame(['ceremony', 'party'], SubmissionNormalizer::eventsFromNormalized($normalized), 'Deduplica gli eventi mantenendo l’ordine.'));
        $assert(static fn () => assertSame(['unknown_flag' => 'keep-me'], rsvpDecodeJsonArray($normalized['metadata'] ?? '[]'), 'Esclude dal metadata i campi già gestiti dal normalizer.'));

        $declined = SubmissionNormalizer::fromPayload([
            'attendance_status' => 'declined',
            'contact_name' => 'Giulia',
            'contact_surname' => 'Verdi',
            'contact_phone' => '123',
            'contact_email' => 'giulia@example.com',
            'participants' => [
                ['name' => 'Da', 'surname' => 'Ignorare'],
            ],
            'accept_privacy_policy' => 'on',
        ]);

        $assert(static fn () => assertSame([], SubmissionNormalizer::participantsFromNormalized($declined), 'Svuota i partecipanti quando la risposta è declined.'));
        $assert(static fn () => assertSame(0, $declined['participants_count'], 'Conta zero partecipanti per risposte declined.'));

        $response = array_merge($normalized, [
            'booking_code' => 'pre_0000042',
            'creation' => '2026-07-17 10:30:00',
            'pretty_attendance_status' => 'Confermato',
            'pretty_accept_privacy_policy' => 'Accettato',
            'pretty_accept_image_release' => 'Rifiutato',
        ]);
        $expanded = ResponseExporter::expandRows([$response]);

        $assert(static fn () => assertSame(2, count($expanded), 'L’export genera una riga per ciascun partecipante.'));
        $assert(static fn () => assertSame('Mario', $expanded[0]['export_participant_name'], 'Mantiene il primo partecipante come prima riga.'));
        $assert(static fn () => assertSame('Adulto', $expanded[0]['export_participant_type'], 'Classifica correttamente un partecipante adulto.'));
        $assert(static fn () => assertSame('No glutine', $expanded[0]['export_participant_dietary_requirements'], 'Esporta le esigenze alimentari del partecipante.'));
        $assert(static fn () => assertSame('30', $expanded[0]['export_participant_age'], 'Esporta l’età del partecipante.'));
        $assert(static fn () => assertSame('Uomo', $expanded[0]['export_participant_sex'], 'Esporta il sesso del partecipante in forma leggibile.'));
        $assert(static fn () => assertSame('Luca', $expanded[1]['export_participant_name'], 'Mantiene il secondo partecipante come seconda riga.'));
        $assert(static fn () => assertSame('Bambino', $expanded[1]['export_participant_type'], 'Classifica correttamente un bambino.'));
        $assert(static fn () => assertSame('pre_0000042', $expanded[1]['booking_code'], 'Duplica i dati della prenotazione sulle righe partecipante.'));
        $assert(static fn () => assertSame('user@example.com', $expanded[1]['contact_email'], 'Duplica i dati del referente sulle righe partecipante.'));

        $declinedResponse = array_merge($declined, [
            'booking_code' => 'pre_0000043',
            'creation' => '2026-07-17 11:00:00',
            'pretty_attendance_status' => 'Non partecipo',
            'pretty_accept_privacy_policy' => 'Accettato',
            'pretty_accept_image_release' => 'Rifiutato',
        ]);
        $declinedRows = ResponseExporter::expandRows([$declinedResponse]);

        $assert(static fn () => assertSame(1, count($declinedRows), 'Mantiene una riga per le risposte senza partecipanti.'));
        $assert(static fn () => assertSame('Giulia', $declinedRows[0]['export_participant_name'], 'Usa il referente come fallback per una risposta declined.'));
        $assert(static fn () => assertSame('Non partecipa', $declinedRows[0]['export_participant_type'], 'Distingue il fallback declined da un partecipante adulto.'));

        $invalidParticipantsResponse = array_replace($response, [
            'participants' => '{"unexpected":["value"]}',
        ]);
        $invalidParticipantsRows = ResponseExporter::expandRows([$invalidParticipantsResponse]);

        $assert(static fn () => assertSame(1, count($invalidParticipantsRows), 'Un payload partecipanti non valido produce una sola riga di fallback.'));
        $assert(static fn () => assertSame('Mario', $invalidParticipantsRows[0]['export_participant_name'], 'Il fallback usa il referente anche per dati partecipante malformati.'));

        $download = (array) ResponseResource::tableLayoutSchema()->get('download');
        $matrix = ResponseExporter::matrix([$response], (array) ($download['columns'] ?? []));
        $firstExportRow = array_combine($matrix[0], $matrix[1]);
        $secondExportRow = array_combine($matrix[0], $matrix[2]);

        $assert(static fn () => assertSame(3, count($matrix), 'La matrice contiene header più una riga per partecipante.'));
        $assert(static fn () => assertSame('Mario', $firstExportRow['Nome'] ?? null, 'Le colonne TableLayout leggono il primo partecipante espanso.'));
        $assert(static fn () => assertSame('Luca', $secondExportRow['Nome'] ?? null, 'Le colonne TableLayout leggono il secondo partecipante espanso.'));
        $assert(static fn () => assertSame('Vegetariano', $secondExportRow['Pasto'] ?? null, 'Ripete e renderizza i custom field su ogni riga partecipante.'));

        Route::reset();
        Route::name('backend.resource.rsvp-responses.')
            ->prefix('/backend/rsvp/responses')
            ->group(static function (): void {
                Route::get('/export/{format}/', __FILE__)->name('export');
                ResponseResource::registerBackendRoutes('', 'rsvp-responses');
            });

        $assert(static fn () => assertSame(
            '/backend/rsvp/responses/participants-export/csv/',
            Route::resolvePath('backend.resource.rsvp-responses.export', ['format' => 'csv']),
            'Il pulsante export standard risolve la route RSVP per partecipante.'
        ));

        fwrite(STDOUT, "Smoke tests passed: {$assertions} assertions\n");
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(1);
    }
}
