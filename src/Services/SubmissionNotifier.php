<?php

namespace Wonder\Plugin\Rsvp\Services;

use Wonder\Plugin\Rsvp\Models\Event;
use Wonder\Plugin\Rsvp\Models\Settings;
use Wonder\Plugin\Rsvp\Models\Response;
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
        $eventEndDate = trim((string) ($event['ends_at'] ?? ''));
        $adminEmail = trim((string) ($settings['admin_email'] ?? ($GLOBALS['SOCIETY']->email ?? '')));
        $customerEmail = trim((string) ($normalized['contact_email'] ?? ''));

        $replacements = [
            'contact_name' => (string) ($normalized['contact_name'] ?? ''),
            'contact_surname' => (string) ($normalized['contact_surname'] ?? ''),
            'event_name' => $eventName,
            'event_starts_at' => prettyDate($eventDate, true),
            'event_ends_at' => $eventEndDate !== '' ? prettyDate($eventEndDate, true) : '',
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
        try {
            $settings = Settings::find(['id' => 1], 1);
        } catch (\Throwable) {
            // Primo avvio del consumer: la tabella può non essere ancora
            // materializzata finché non gira `php forge update --local`.
            $settings = [];
        }

        return is_array($settings) ? $settings : [];
    }

    public static function defaults(): array
    {
        return [
            'customer_subject' => __t('emails.rsvp_request_customer.subject'),
            'customer_message' => __t('emails.rsvp_request_customer.text'),
            'admin_subject' => __t('emails.rsvp_request_admin.subject'),
            'admin_message' => __t('emails.rsvp_request_admin.text'),
        ];
    }

    private static function responseUrl(int $responseId): string
    {
        return __r('backend.resource.'.ResponseResource::slug().'.view', ['id' => $responseId]);
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
                'name' => __t('pages.rsvp.home.title'),
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

        return __t($key, $replacements);
    }

    private static function summaryHtml(array $normalized): string
    {
        $participants = SubmissionNormalizer::participantsFromNormalized($normalized);
        $consents = SubmissionNormalizer::consents($normalized);
        $documents = SubmissionNormalizer::legalDocumentsFromNormalized($normalized);
        $event = self::eventFromNormalized($normalized);
        $showAttendanceStatus = rsvpAttendanceStatusEnabled(self::settings());
        $lines = [];

        $lines[] = __t('emails.summary.name')
            .': <strong>'.htmlspecialchars(trim(((string) ($normalized['contact_name'] ?? '')).' '.((string) ($normalized['contact_surname'] ?? ''))), ENT_QUOTES, 'UTF-8').'</strong>';
        if ($showAttendanceStatus) {
            $lines[] = __t('emails.summary.attendance_status')
                .': <strong>'.htmlspecialchars(rsvpAttendanceStatusText($normalized['attendance_status'] ?? null), ENT_QUOTES, 'UTF-8').'</strong>';
        }
        $lines[] = __t('emails.summary.email')
            .': <strong>'.htmlspecialchars((string) ($normalized['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8').'</strong>';
        $lines[] = __t('emails.summary.phone')
            .': <strong>'.htmlspecialchars((string) ($normalized['contact_phone'] ?? '--'), ENT_QUOTES, 'UTF-8').'</strong>';
        $lines[] = __t('emails.summary.participants')
            .': <strong>'.(int) ($normalized['participants_count'] ?? 0).'</strong>';
        $lines[] = __t('emails.summary.children')
            .': <strong>'.(int) ($normalized['children_count'] ?? 0).'</strong>';

        if (!empty($normalized['event_key'])) {
            $lines[] = __t('emails.summary.primary_event')
                .': <strong>'.htmlspecialchars((string) ($event['name'] ?? $normalized['event_key']), ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['invite_code'] ?? '') !== '') {
            $lines[] = __t('emails.summary.invite_code')
                .': <strong>'.htmlspecialchars((string) $normalized['invite_code'], ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['authorization_code'] ?? '') !== '') {
            $lines[] = __t('emails.summary.authorization')
                .': <strong>'.htmlspecialchars((string) $normalized['authorization_code'], ENT_QUOTES, 'UTF-8').'</strong>';
        }

        if (($normalized['notes'] ?? '') !== '') {
            $lines[] = __t('emails.summary.notes')
                .': <strong>'.nl2br(htmlspecialchars((string) $normalized['notes'], ENT_QUOTES, 'UTF-8')).'</strong>';
        }

        foreach (Response::customFieldDefinitions() as $field) {
            $value = rsvpRenderCustomFieldValue($field, $normalized[$field['column']] ?? null);

            if ($value === '') {
                continue;
            }

            $lines[] = htmlspecialchars(rsvpCustomFieldLabel($field, (string) ($field['key'] ?? '')), ENT_QUOTES, 'UTF-8')
                .': <strong>'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'</strong>';
        }

        $participantList = [];

        foreach ($participants as $participant) {
            $label = trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? '')));
            $suffix = !empty($participant['is_child'])
                ? ' ('.__t('emails.summary.child_suffix').')'
                : '';
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
            $lines[] = __t('emails.summary.participant_list')
                .':<ul>'.implode('', $participantList).'</ul>';
        }

        $documentLines = [];

        foreach ($documents as $docType => $document) {
            $documentLines[] = htmlspecialchars(rsvpLegalDocumentLabel((string) $docType), ENT_QUOTES, 'UTF-8')
                .': <strong>'.rsvpBooleanText($document['accepted'] ?? false).'</strong>';
        }
        
        if ($documentLines !== []) {
            $lines[] = __t('emails.summary.documents')
                .':<br>'.implode('<br>', $documentLines);
        }

        return implode('<br>', $lines);
    }
}
