<?php

use Wonder\Elements\Components\Card;
use Wonder\Elements\Components\Container;
use Wonder\Elements\Components\Link;
use Wonder\Elements\Components\RichText;
use Wonder\Elements\Components\SectionTitle;
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Services\SubmissionNotifier;

$item = is_array($ITEM ?? null) ? $ITEM : [];
$participants = json_decode((string) ($item['participants_json'] ?? '[]'), true) ?: [];
$events = json_decode((string) ($item['events_json'] ?? '[]'), true) ?: [];
$documents = json_decode((string) ($item['legal_documents_json'] ?? '[]'), true) ?: [];
$metadata = json_decode((string) ($item['metadata_json'] ?? '[]'), true) ?: [];
$customFields = Response::customFieldDefinitions();
$bookingCode = (string) ($item['booking_code'] ?? '');
$showAttendanceStatus = rsvpAttendanceStatusEnabled(SubmissionNotifier::settings());

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$fullName = trim(((string) ($item['contact_name'] ?? '')).' '.((string) ($item['contact_surname'] ?? '')));

\Wonder\View\View::layout('backend.show', [
    'TITLE' => $fullName !== '' ? $fullName : 'Risposta RSVP',
    'SUBTITLE' => 'Prenotazione '.($bookingCode !== '' ? $bookingCode : '--')
        .' del '.((string) ($item['creation'] ?? '--')),
]);

$participantsHtml = 'Totale: <b>'.count($participants).'</b><br>'
    .'Bambini: <b>'.$e($item['children_count'] ?? '0').'</b><br>';

foreach ($participants as $index => $participant) {
    $name = trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? '')));
    $participantsHtml .= 'Partecipante '.($index + 1).': <b>'.$e($name).'</b>';

    if (!empty($participant['is_child'])) {
        $participantsHtml .= ' <em>(bambino)</em>';
    }

    if (!empty($participant['dietary_requirements'])) {
        $participantsHtml .= ' - '.$e($participant['dietary_requirements']);
    }

    $participantsHtml .= '<br>';
}

$mainCards = [
    (new Card)->components([
        (new Container)->components([
            (new SectionTitle)->text('Contatto'),
            (new RichText(
                'Nome: <b>'.$e($fullName).'</b><br>'
                .'Email: <b>'.$e($item['contact_email'] ?? '--').'</b><br>'
                .'Telefono: <b>'.$e($item['contact_phone'] ?? '--').'</b>'
            )),
        ])->columnSpan(1),
        (new Container)->components([
            (new SectionTitle)->text('Partecipanti'),
            (new RichText($participantsHtml)),
        ])->columnSpan(1),
    ])->columns(2),

    (new Card)->components([
        (new SectionTitle)->text('Richieste'),
        (new RichText(nl2br($e($item['notes'] ?? '--')))),
    ]),
];

$customFieldsHtml = '';
foreach ($customFields as $field) {
    $value = rsvpRenderCustomFieldValue($field, $item[$field['column']] ?? null);

    if ($value === '') {
        continue;
    }

    $customFieldsHtml .= $e(rsvpCustomFieldLabel($field, (string) ($field['key'] ?? '')))
        .': <b>'.$e($value).'</b><br>';
}

if ($customFieldsHtml !== '') {
    $mainCards[] = (new Card)->components([
        (new SectionTitle)->text('Campi personalizzati'),
        (new RichText($customFieldsHtml)),
    ]);
}

if ($metadata !== []) {
    $mainCards[] = (new Card)->components([
        (new SectionTitle)->text('Dati aggiuntivi'),
        (new RichText('<pre class="mb-0">'.$e(json_encode(
            $metadata,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )).'</pre>')),
    ]);
}

$detailsHtml = '';
if ($showAttendanceStatus) {
    $detailsHtml .= 'Conferma: <b>'.$e($item['pretty_attendance_status'] ?? '').'</b><br>';
}
$detailsHtml .= 'Codice prenotazione: <b>'.$e($bookingCode !== '' ? $bookingCode : '--').'</b><br>'
    .'Creazione: <b>'.$e($item['creation'] ?? '--').'</b><br>'
    .'Lingua: <b>'.$e($item['locale'] ?? '--').'</b><br>'
    .'Evento: <b>'.$e($item['event_key'] ?? '--').'</b><br>'
    .'Codice invito: <b>'.$e($item['invite_code'] ?? '--').'</b><br>'
    .'Gruppo invito: <b>'.$e($item['invite_group_code'] ?? '--').'</b><br>'
    .'Autorizzazione: <b>'.$e($item['authorization_code'] ?? '--').'</b>';

$sideCards = [
    (new Card)->components([
        (new SectionTitle)->text('Dettagli'),
        (new RichText($detailsHtml)),
    ]),

    (new Card)->components([
        (new SectionTitle)->text('Consensi'),
        (new RichText(
            'Privacy: <b>'.$e($item['pretty_accept_privacy_policy'] ?? '').'</b><br>'
            .'Foto: <b>'.$e($item['pretty_accept_image_release'] ?? '').'</b>'
        )),
    ]),
];

if ($documents !== []) {
    $documentsHtml = '';
    foreach ($documents as $docType => $document) {
        $documentsHtml .= $e($docType).': <b>'.$e(rsvpBooleanText($document['accepted'] ?? false)).'</b><br>';
    }

    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Documenti legali'),
        (new RichText($documentsHtml)),
    ]);
}

if ($events !== []) {
    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Eventi selezionati'),
        (new RichText(implode('<br>', array_map($e, $events)))),
    ]);
}

if (!empty($item['request_url'])) {
    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Origine'),
        (new RichText((string) (new Link((string) $item['request_url']))
            ->label((string) $item['request_url'])
            ->blank())),
    ]);
}

echo (new Container)->components([
    (new Container)->components($mainCards)->columnSpan(2)->columns(1),
    (new Container)->components($sideCards)->columnSpan(1)->columns(1),
])->columns(3);

\Wonder\View\View::end();
