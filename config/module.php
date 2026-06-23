<?php

use Wonder\Consent\LegalDocumentTypeContext;
use Wonder\Localization\LanguageContext;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Services\LegalDocumentSeeder;
use Wonder\View\ComponentNamespaceRegistry;

LegalDocumentTypeContext::addType('image_release', 'Liberatoria immagini');
LanguageContext::addUrlsPath(Rsvp::langPath());
LegalDocumentSeeder::ensureImageRelease();
ComponentNamespaceRegistry::register('rsvp', Rsvp::root().'/view/components');

return [
    'legal_documents' => [
        'image_release_type' => 'image_release',
    ],
];
