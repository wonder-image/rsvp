<?php

    $TABLE->RSVP = [
        "name" => [],
        "surname" => [],
        "participants" => [],
        "cel" => [],
        "email" => [],
        "events" => [],
        "allergies" => [],
        "requests" => []
    ];

    $TABLE->DETAILS = [
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

?>