<?php

    $TABLE->RSVP = [
        "password_id" => [],
        "name" => [],
        "surname" => [],
        "participants" => [],
        "cel" => [],
        "email" => [],
        "events" => [],
        "allergies" => [],
        "requests" => [],
        "lang" => []
    ];

    $TABLE->RSVP_DETAILS = [
        "name" => [],
        "date" => [
            "sql" => [
                "type" => "datetime"
            ],
            "input" => [
                "format" => "date"
            ]
        ]
    ];

    $TABLE->RSVP_AUTHORITY = [
        "code" => [],
        "name" => []
    ];

    $TABLE->RSVP_PASSWORD = [
        "password" => [],
        "type" => [],
        "authority_id" => [],
        "active" => []
    ];

?>