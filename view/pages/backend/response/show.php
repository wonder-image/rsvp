<?php

use Wonder\Elements\Components\{ Card, Container, Link, RichText, SectionTitle };
use Wonder\Plugin\Rsvp\Models\Response;
use Wonder\Plugin\Rsvp\Services\SubmissionNotifier;

$RESPONSE = Response::safeFindById($ITEM['id'] ?? 0) ?? [];
$customFields = Response::customFieldDefinitions();

$showAttendanceStatus = rsvpAttendanceStatusEnabled(SubmissionNotifier::settings());

$fullName = trim(($RESPONSE['contact_name'] ?? '').' '.($RESPONSE['contact_surname'] ?? ''));

\Wonder\View\View::layout('backend.show', [
    'TITLE' => $fullName !== '' ? $fullName : 'Risposta RSVP',
    'SUBTITLE' => 'Prenotazione '.$RESPONSE['booking_code'].' del '.prettyDate($RESPONSE['creation'], true),
]);

$participants = $RESPONSE['participants'] ?? [];

$participantsHtml = 'Totale: <b>'.count($participants).'</b><br>'
    .'Bambini: <b>'.e($RESPONSE['children_count'] ?? '0').'</b><br>';

foreach ($participants as $index => $participant) {
    $name = trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? '')));
    $participantsHtml .= 'Partecipante '.($index + 1).': <b>'.e($name).'</b>';

    if (!empty($participant['is_child'])) {
        $participantsHtml .= ' <em>(bambino)</em>';
    }

    if (!empty($participant['dietary_requirements'])) {
        $participantsHtml .= ' - '.e($participant['dietary_requirements']);
    }

    $participantsHtml .= '<br>';
}

$mainCards = [
    (new Card)->components([
        (new Container)->components([
            (new SectionTitle)->text('Contatto'),
            (new RichText(
                'Codice prenotazione: <b>'.e($RESPONSE['booking_code'] ?? '--').'</b><br>'
                .'Nome: <b>'.e($fullName).'</b><br>'
                .'Email: <b>'.e($RESPONSE['contact_email'] ?? '--').'</b><br>'
                .'Telefono: <b>'.e($RESPONSE['contact_phone'] ?? '--').'</b>'
            )),
        ])->columnSpan(1),
        (new Container)->components([
            (new SectionTitle)->text('Partecipanti'),
            (new RichText($participantsHtml)),
        ])->columnSpan(1),
    ])->columns(2),

    (new Card)->components([
        (new SectionTitle)->text('Richieste'),
        (new RichText(nl2br(e($RESPONSE['notes'] ?? '--')))),
    ]),
];

$customFieldsHtml = '';
foreach ($customFields as $field) {
    $value = rsvpRenderCustomFieldValue($field, $RESPONSE[$field['column']] ?? null);

    if ($value === '') {
        continue;
    }

    $customFieldsHtml .= e(rsvpCustomFieldLabel($field, (string) ($field['key'] ?? '')))
        .': <b>'.e($value).'</b><br>';
}

if ($customFieldsHtml !== '') {
    $mainCards[] = (new Card)->components([
        (new SectionTitle)->text('Campi personalizzati'),
        (new RichText($customFieldsHtml)),
    ]);
}

if (($RESPONSE['metadata'] ?? []) !== []) {
    $mainCards[] = (new Card)->components([
        (new SectionTitle)->text('Dati aggiuntivi'),
        (new RichText('<pre class="mb-0">'.e(js_e( $RESPONSE['metadata'] )).'</pre>')),
    ]);
}

$detailsHtml = '';
if ($showAttendanceStatus) {
    $detailsHtml .= 'Conferma: <b>'.e($RESPONSE['pretty_attendance_status'] ?? '').'</b><br>';
}
$detailsHtml .= 'Lingua: <b>'.e($RESPONSE['locale'] ?? '--').'</b><br>'
    .'Codice invito: <b>'.e($RESPONSE['invite_code'] ?? '--').'</b><br>'
    .'Gruppo invito: <b>'.e($RESPONSE['invite_group_code'] ?? '--').'</b><br>'
    .'Autorizzazione: <b>'.e($RESPONSE['authorization_code'] ?? '--').'</b>';

$sideCards = [
    (new Card)->components([
        (new SectionTitle)->text('Dettagli'),
        (new RichText($detailsHtml)),
    ]),
];

if (($RESPONSE['legal_documents'] ?? []) !== []) {
    $documentsHtml = '';
    foreach ($RESPONSE['legal_documents'] as $docType => $document) {
        $documentsHtml .= e(rsvpLegalDocumentLabel((string) $docType) ?: (string) $docType)
            .': <b>'.e(rsvpBooleanText($document['accepted'] ?? false)).'</b><br>';
    }

    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Documenti legali'),
        (new RichText($documentsHtml)),
    ]);
}

if (($RESPONSE['events'] ?? []) !== []) {
    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Eventi selezionati'),
        (new RichText(implode('<br>', array_map('e', $RESPONSE['events'])))),
    ]);
}

if (!empty($RESPONSE['request_url'])) {
    $sideCards[] = (new Card)->components([
        (new SectionTitle)->text('Origine'),
        (new RichText((string) (new Link((string) $RESPONSE['request_url']))
            ->label((string) $RESPONSE['request_url'])
            ->blank())),
    ]);
}

echo (new Container)->components([
    (new Container)->components($mainCards)->columnSpan(2)->columns(1),
    (new Container)->components($sideCards)->columnSpan(1)->columns(1),
])->columns(3);

\Wonder\View\View::end();
