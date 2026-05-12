<?php

use Wonder\Consent\LegalDocumentTypeContext;
use Wonder\Localization\LanguageContext;
use Wonder\Plugin\Rsvp\Rsvp;
use Wonder\Plugin\Rsvp\Support\LegalDocumentSeeder;

LegalDocumentTypeContext::addType('image_release', 'Liberatoria immagini');
LanguageContext::addUrlsPath(Rsvp::langPath());
LegalDocumentSeeder::ensureImageRelease();

return [
    'legal_documents' => [
        'image_release_type' => 'image_release',
    ],
];
