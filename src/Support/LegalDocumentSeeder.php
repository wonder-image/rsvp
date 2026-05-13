<?php

namespace Wonder\Plugin\Rsvp\Support;

use Throwable;
use Wonder\App\Models\Config\LegalDocument;

final class LegalDocumentSeeder
{
    public static function ensureImageRelease(): void
    {
        if (!class_exists(LegalDocument::class)) {
            return;
        }

        try {
            if (!LegalDocument::query()->TableExists(LegalDocument::$table)) {
                return;
            }
        } catch (Throwable $exception) {
            return;
        }

        foreach (self::languages() as $languageCode) {
            $existing = LegalDocument::find([
                'doc_type' => 'image_release',
                'language_code' => $languageCode,
                'active' => 'true',
            ], 1);

            if (is_array($existing) && $existing !== []) {
                continue;
            }

            $payload = self::payload($languageCode);
            $blocks = function_exists('contentsToEditorBlocks')
                ? contentsToEditorBlocks((string) $payload['content'])
                : [[
                    'type' => 'paragraph',
                    'data' => [
                        'text' => (string) $payload['content'],
                    ],
                ]];

            $contentSnapshot = json_encode(
                $blocks,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if (!is_string($contentSnapshot) || $contentSnapshot === '') {
                continue;
            }

            LegalDocument::create([
                'doc_type' => 'image_release',
                'name' => $payload['name'],
                'version' => '1.0.0',
                'language_code' => $languageCode,
                'checkbox_label' => $payload['checkbox_label'],
                'content_hash' => hash('sha256', $contentSnapshot),
                'content_snapshot' => $contentSnapshot,
                'published_at' => date('Y-m-d H:i:s'),
                'active' => 'true',
            ]);
        }
    }

    /**
     * @return string[]
     */
    private static function languages(): array
    {
        $languages = rsvp_locales('it');

        if ($languages === []) {
            $languages[] = rsvp_locale('it');
        }

        $normalized = [];

        foreach ($languages as $language) {
            $language = strtolower(trim((string) $language));

            if ($language !== '') {
                $normalized[] = $language;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array{name:string,checkbox_label:string,content:string}
     */
    private static function payload(string $languageCode): array
    {
        return match (strtolower(trim($languageCode))) {
            'en' => [
                'name' => 'Photo and Video Release',
                'checkbox_label' => 'I consent to the collection and use of photos and videos.',
                'content' => "",
            ],
            default => [
                'name' => 'Liberatoria immagini',
                'checkbox_label' => 'Acconsento alla raccolta e all’utilizzo di immagini fotografiche e video.',
                'content' => "",
            ],
        };
    }
}
