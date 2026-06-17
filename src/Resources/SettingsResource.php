<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\App\ResourceSchema\FormField;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\Resources\Support\SingletonResource;
use Wonder\Elements\Components\Card;
use Wonder\Elements\Form\Form;
use Wonder\Plugin\Rsvp\Models\Settings;

final class SettingsResource extends SingletonResource
{
    public static string $model = Settings::class;

    public static function textSchema(): array
    {
        return [
            'label' => 'impostazione RSVP',
            'plural_label' => 'impostazioni RSVP',
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
            'require_invite_code' => 'Accesso con codice',
            'enable_attendance_status' => 'Abilita partecipo/non partecipo',
            'max_participants' => 'Max adulti',
            'allow_children' => 'Bambini',
            'max_children' => 'Max bambini',
            'require_image_release' => 'Richiedi liberatoria immagini',
            'admin_email' => 'Email notifiche',
            'admin_notifications' => 'Invia email admin',
            'customer_notifications' => 'Invia email ospite',
            'customer_subject' => 'Oggetto email ospite',
            'customer_message' => 'Messaggio email ospite',
            'admin_subject' => 'Oggetto email admin',
            'admin_message' => 'Messaggio email admin',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormField::key('require_invite_code')->select([
                'false' => 'Libero',
                'true' => 'Richiede codice',
            ])->value('false'),
            FormField::key('enable_attendance_status')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->value('false'),
            FormField::key('max_participants')->number(),
            FormField::key('allow_children')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->value('false'),
            FormField::key('max_children')->number(),
            FormField::key('require_image_release')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->value('false'),
            FormField::key('admin_email')->email(),
            FormField::key('admin_notifications')->select([
                'true' => 'Sì',
                'false' => 'No',
            ])->value('true'),
            FormField::key('customer_notifications')->select([
                'true' => 'Sì',
                'false' => 'No',
            ])->value('true'),
            FormField::key('customer_subject')->text(),
            FormField::key('customer_message')->textarea(),
            FormField::key('admin_subject')->text(),
            FormField::key('admin_message')->textarea(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('customer_subject')->columnSpan(12),
                static::getInput('customer_message')->columnSpan(12),
                static::getInput('admin_subject')->columnSpan(12),
                static::getInput('admin_message')->columnSpan(12),
            ])->columns(12)->columnSpan(9),
            (new Card)->components([
                static::getInput('require_invite_code')->columnSpan(12),
                static::getInput('enable_attendance_status')->columnSpan(12),
                static::getInput('max_participants')->columnSpan(12),
                static::getInput('allow_children')->columnSpan(12),
                static::getInput('max_children')->columnSpan(12),
                static::getInput('require_image_release')->columnSpan(12),
                static::getInput('admin_email')->columnSpan(12),
                static::getInput('admin_notifications')->columnSpan(12),
                static::getInput('customer_notifications')->columnSpan(12),
            ])->columns(12)->columnSpan(3),
        ])->columns(12);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('admin_email')->text(),
            TableColumn::key('require_invite_code')->text(),
        ];
    }

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        return $values;
    }

    public static function mutateFormValues(
        array $values,
        string $mode,
        string $context = 'backend'
    ): array {
        $defaults = \Wonder\Plugin\Rsvp\Services\SubmissionNotifier::defaults();

        $values['customer_subject'] = trim((string) ($values['customer_subject'] ?? '')) !== ''
            ? (string) $values['customer_subject']
            : $defaults['customer_subject'];
        $values['customer_message'] = trim((string) ($values['customer_message'] ?? '')) !== ''
            ? (string) $values['customer_message']
            : $defaults['customer_message'];
        $values['admin_subject'] = trim((string) ($values['admin_subject'] ?? '')) !== ''
            ? (string) $values['admin_subject']
            : $defaults['admin_subject'];
        $values['admin_message'] = trim((string) ($values['admin_message'] ?? '')) !== ''
            ? (string) $values['admin_message']
            : $defaults['admin_message'];

        return $values;
    }

    public static function navigationSchema(): NavigationSchema
    {
        return NavigationSchema::for(static::class)
            ->section('RSVP', 'rsvp', 'bi-ticket-perforated')
            ->title('Impostazioni')
            ->order(40)
            ->authority(['admin']);
    }
}
