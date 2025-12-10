<?php

    // Info evento
    if (sqlTableExists('rsvp_details')) {

        $EVENT = info('rsvp_details', 'id', '1');
        $EVENT->datePretty = date('d.m.Y', strtotime($EVENT->date));    

        # Imposto le traduzioni personalizzate
        Wonder\Localization\TranslationProvider::setGlobals([
            'event_name' => $EVENT->name,
            'event_date' => prettyDate($EVENT->date),
            'event_date_hour' => prettyDate($EVENT->date, true),
            'event_address' => $SOCIETY->prettyAddress,
            'event_gmaps' => $SOCIETY->gmaps
        ]);
        
    }

    use Wonder\Localization\{ LanguageContext };

    # Imposto le lingue
    LanguageContext::addLangPath($ROOT.'/lang/')
        ::defaultLang('it')
        ::addLanguage('en', 'English', "https://www.$PAGE->domain/en/", 'gb', [])
        ::addLanguage('it', 'Italiano', "https://www.$PAGE->domain/it/", 'it', ['IT'])
        ::setLangFromPath();

    # Imposto le variabili globali
    TranslationProvider::setGlobals([
        
        'path_site' => __u(),
        'path_privacy_policy' => __u('legal/privacy-policy'),
        'path_cookie_policy' => __u('legal/cookie-policy'),
        'path_terms_conditions' => __u('legal/terms-conditions'),
        
    ]);