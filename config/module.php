<?php

use Wonder\Consent\LegalDocumentTypeContext;
use Wonder\Localization\LanguageContext;
use Wonder\Plugin\Rsvp\Rsvp;

LegalDocumentTypeContext::addType('image_release', 'Liberatoria immagini');
LanguageContext::addUrlsPath(Rsvp::langPath());

return [
    'legal_documents' => [
        'image_release_type' => 'image_release',
    ],
];
