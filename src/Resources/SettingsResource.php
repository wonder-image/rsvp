<?php

namespace Wonder\Plugin\Rsvp\Resources;

use Wonder\App\ResourceSchema\FormInput;
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
            'event_name' => 'Nome evento',
            'event_starts_at' => 'Data evento',
            'location_name' => 'Location',
            'location_url' => 'Link location',
            'require_invite_code' => 'Accesso con codice',
            'login_title' => 'Titolo login',
            'login_text' => 'Testo login',
            'home_title' => 'Titolo RSVP',
            'home_text' => 'Testo RSVP',
            'event_options_json' => 'Catalogo eventi',
            'events_catalog_json' => 'Catalogo eventi esteso',
            'page_content_json' => 'Contenuti pagina multilingua',
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
            FormInput::key('event_name')->text(),
            FormInput::key('event_starts_at')->textDatetime(),
            FormInput::key('location_name')->text(),
            FormInput::key('location_url')->url(),
            FormInput::key('require_invite_code')->select([
                'false' => 'Libero',
                'true' => 'Richiede codice',
            ])->value('false'),
            FormInput::key('login_title')->text(),
            FormInput::key('login_text')->textarea(),
            FormInput::key('home_title')->text(),
            FormInput::key('home_text')->textarea(),
            FormInput::key('event_options_json')->textarea()->prepare('sanitize', false),
            FormInput::key('events_catalog_json')->textarea('blog')->prepare('sanitize', false),
            FormInput::key('page_content_json')->textarea('blog')->prepare('sanitize', false),
            FormInput::key('max_participants')->number(),
            FormInput::key('allow_children')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->value('false'),
            FormInput::key('max_children')->number(),
            FormInput::key('require_image_release')->select([
                'false' => 'No',
                'true' => 'Sì',
            ])->value('false'),
            FormInput::key('admin_email')->email(),
            FormInput::key('admin_notifications')->select([
                'true' => 'Sì',
                'false' => 'No',
            ])->value('true'),
            FormInput::key('customer_notifications')->select([
                'true' => 'Sì',
                'false' => 'No',
            ])->value('true'),
            FormInput::key('customer_subject')->text(),
            FormInput::key('customer_message')->textarea('blog'),
            FormInput::key('admin_subject')->text(),
            FormInput::key('admin_message')->textarea('blog'),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('event_name')->columnSpan(8),
                static::getInput('event_starts_at')->columnSpan(4),
                static::getInput('location_name')->columnSpan(6),
                static::getInput('location_url')->columnSpan(6),
                static::getInput('login_title')->columnSpan(12),
                static::getInput('login_text')->columnSpan(12),
                static::getInput('home_title')->columnSpan(12),
                static::getInput('home_text')->columnSpan(12),
                static::getInput('event_options_json')->columnSpan(12),
                static::getInput('events_catalog_json')->columnSpan(12),
                static::getInput('page_content_json')->columnSpan(12),
                static::getInput('customer_subject')->columnSpan(12),
                static::getInput('customer_message')->columnSpan(12),
                static::getInput('admin_subject')->columnSpan(12),
                static::getInput('admin_message')->columnSpan(12),
            ])->columns(12)->columnSpan(9),
            (new Card)->components([
                static::getInput('require_invite_code')->columnSpan(12),
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
            TableColumn::key('event_name')->text(),
            TableColumn::key('event_starts_at')->datetime()->size('medium'),
        ];
    }

    public static function mutateRequestValues(
        array $values,
        string $action,
        string $context = 'backend',
        ?array $oldValues = null
    ): array {
        $values['event_options_json'] = json_encode(
            rsvpParseMapText((string) ($values['event_options_json'] ?? '')),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $values['events_catalog_json'] = json_encode(
            rsvpDecodeJsonOrDefault($values['events_catalog_json'] ?? '', []),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $values['page_content_json'] = json_encode(
            rsvpDecodeJsonOrDefault($values['page_content_json'] ?? '', []),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $values;
    }

    public static function mutateFormValues(
        array $values,
        string $mode,
        string $context = 'backend'
    ): array {
        $values['event_options_json'] = rsvpFormatMapText(
            rsvpDecodeJsonArray($values['event_options_json'] ?? '[]')
        );
        $values['events_catalog_json'] = rsvpEnsureJsonTextarea(
            $values['events_catalog_json'] ?? '[]'
        );
        $values['page_content_json'] = rsvpEnsureJsonTextarea(
            $values['page_content_json'] ?? '[]'
        );

        if (!empty($values['event_starts_at']) && strtotime((string) $values['event_starts_at']) !== false) {
            $values['event_starts_at'] = date('Y-m-d\TH:i', strtotime((string) $values['event_starts_at']));
        }

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
