<?php

namespace Wonder\Plugin\Rsvp\Support;

use Wonder\Plugin\Rsvp\Models\Event;
use Wonder\Plugin\Rsvp\Models\Settings;
use Wonder\Plugin\Rsvp\Resources\ResponseResource;

final class SubmissionNotifier
{
    public static function notify(array $normalized, int $responseId): void
    {
        $settings = self::settings();
        $summaryHtml = self::summaryHtml($normalized);
        $responseUrl = self::responseUrl($responseId);
        $event = self::eventFromNormalized($normalized);
        $defaults = self::defaults();
        $eventName = trim((string) ($event['name'] ?? ''));
        $eventDate = trim((string) ($event['starts_at'] ?? ''));
        $adminEmail = trim((string) ($settings['admin_email'] ?? ($GLOBALS['SOCIETY']->email ?? '')));
        $customerEmail = trim((string) ($normalized['contact_email'] ?? ''));

        $replacements = [
            'contact_name' => (string) ($normalized['contact_name'] ?? ''),
            'contact_surname' => (string) ($normalized['contact_surname'] ?? ''),
            'event_name' => $eventName,
            'event_starts_at' => $eventDate,
            'summary_html' => $summaryHtml,
            'response_url' => $responseUrl,
        ];

        if ($adminEmail !== '' && ($settings['admin_notifications'] ?? 'true') === 'true') {
            sendMail(
                $GLOBALS['SOCIETY']->email ?? $adminEmail,
                $adminEmail,
                self::message(
                    (string) ($settings['admin_subject'] ?? ''),
                    'emails.rsvp_request_admin.subject',
                    $defaults['admin_subject'],
                    $replacements
                ),
                self::message(
                    (string) ($settings['admin_message'] ?? ''),
                    'emails.rsvp_request_admin.text',
                    $defaults['admin_message'],
                    $replacements
                )
            );
        }

        if ($customerEmail !== '' && ($settings['customer_notifications'] ?? 'true') === 'true') {
            sendMail(
                $GLOBALS['SOCIETY']->email ?? $adminEmail,
                $customerEmail,
                self::message(
                    (string) ($settings['customer_subject'] ?? ''),
                    'emails.rsvp_request_customer.subject',
                    $defaults['customer_subject'],
                    $replacements
                ),
                self::message(
                    (string) ($settings['customer_message'] ?? ''),
                    'emails.rsvp_request_customer.text',
                    $defaults['customer_message'],
                    $replacements
                )
            );
        }
    }

    public static function settings(): array
    {
        $settings = Settings::find(['id' => 1], 1);

        return is_array($settings) ? $settings : [];
    }

    public static function defaults(): array
    {
        return [
            'customer_subject' => 'Conferma RSVP ricevuta',
            'customer_message' => 'Ciao {{contact_name}},<br>abbiamo ricevuto correttamente la tua risposta RSVP'
                .' per <strong>{{event_name}}</strong>.'
                .'<br><br>{{summary_html}}',
            'admin_subject' => 'Nuova risposta RSVP - {{contact_name}} {{contact_surname}}',
            'admin_message' => 'È arrivata una nuova risposta RSVP per <strong>{{event_name}}</strong>.'
                .'<br><br>{{summary_html}}'
                .'<br><br><a href="{{response_url}}">Apri il dettaglio in backend</a>',
        ];
    }

    private static function responseUrl(int $responseId): string
    {
        return function_exists('__r')
            ? __r('backend.resource.'.ResponseResource::slug().'.view', ['id' => $responseId])
            : '';
    }

    private static function eventFromNormalized(array $normalized): array
    {
        $eventKey = trim((string) ($normalized['event_key'] ?? ''));

        if ($eventKey === '') {
            $events = SubmissionNormalizer::eventsFromNormalized($normalized);
            $eventKey = trim((string) ($events[0] ?? ''));
        }

        if ($eventKey === '') {
            return [
                'name' => 'RSVP',
                'starts_at' => '',
            ];
        }

        $event = Event::find([
            'code' => $eventKey,
            'deleted' => 'false',
        ], 1);

        return is_array($event) && $event !== []
            ? $event
            : [
                'name' => $eventKey,
                'starts_at' => '',
            ];
    }

    private static function message(string $configured, string $key, string $fallback, array $replacements): string
    {
        $configured = trim($configured);

        if ($configured !== '') {
            foreach ($replacements as $replacementKey => $replacementValue) {
                $configured = str_replace('{{'.$replacementKey.'}}', (string) $replacementValue, $configured);
            }

            return $configured;
        }

        return rsvp_trans($key, $fallback, $replacements);
    }

    private static function summaryHtml(array $normalized): string
    {
        $participants = SubmissionNormalizer::participantsFromNormalized($normalized);
        $consents = SubmissionNormalizer::consents($normalized);
        $documents = SubmissionNormalizer::legalDocumentsFromNormalized($normalized);
        $event = self::eventFromNormalized($normalized);
        $lines = [];

        $lines[] = 'Nome: <strong>'.htmlspecialchars(trim(((string) ($normalized['contact_name'] ?? '')).' '.((string) ($normalized['contact_surname'] ?? ''))), ENT_QUOTES, 'UTF-8').'</strong>';
        $lines[] = 'Email: <strong>'.htmlspecialchars((string) ($normalized['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8').'</strong>';
        $lines[] = 'Telefono: <strong>'.htmlspecialchars((string) ($normalized['contact_phone'] ?? '--'), ENT_QUOTES, 'UTF-8').'</strong>';
        $lines[] = 'Partecipanti: <strong>'.(int) ($normalized['participants_count'] ?? 0).'</strong>';
        $lines[] = 'Bambini: <strong>'.(int) ($normalized['children_count'] ?? 0).'</strong>';

        if (!empty($normalized['event_key'])) {
            $lines[] = 'Evento principale: <strong>'.htmlspecialchars((string) ($event['name'] ?? $normalized['event_key']), ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['invite_code'] ?? '') !== '') {
            $lines[] = 'Codice invito: <strong>'.htmlspecialchars((string) $normalized['invite_code'], ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['authorization_code'] ?? '') !== '') {
            $lines[] = 'Autorizzazione: <strong>'.htmlspecialchars((string) $normalized['authorization_code'], ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['notes'] ?? '') !== '') {
            $lines[] = 'Richieste: <strong>'.nl2br(htmlspecialchars((string) $normalized['notes'], ENT_QUOTES, 'UTF-8')).'</strong>';
        }

        $participantList = [];

        foreach ($participants as $participant) {
            $label = trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? '')));
            $suffix = !empty($participant['is_child']) ? ' (bambino)' : '';
            $dietary = trim((string) ($participant['dietary_requirements'] ?? ''));
            $item = htmlspecialchars($label.$suffix, ENT_QUOTES, 'UTF-8');

            if ($dietary !== '') {
                $item .= ' - '.htmlspecialchars($dietary, ENT_QUOTES, 'UTF-8');
            }

            if ($label !== '' || $dietary !== '') {
                $participantList[] = '<li>'.$item.'</li>';
            }
        }

        if ($participantList !== []) {
            $lines[] = 'Elenco partecipanti:<ul>'.implode('', $participantList).'</ul>';
        }

        $documentLines = [];

        foreach ($documents as $docType => $document) {
            $documentLines[] = htmlspecialchars($docType, ENT_QUOTES, 'UTF-8').': <strong>'.rsvpBooleanText($document['accepted'] ?? false).'</strong>';
        }

        $lines[] = 'Privacy: <strong>'.rsvpBooleanText($consents['privacy'] ?? false).'</strong>';
        $lines[] = 'Foto: <strong>'.rsvpBooleanText($consents['photo'] ?? false).'</strong>';

        if ($documentLines !== []) {
            $lines[] = 'Documenti: '.implode('<br>', $documentLines);
        }

        return implode('<br>', $lines);
    }
}
