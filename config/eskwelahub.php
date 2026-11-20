<?php

return [

    /*
    |--------------------------------------------------------------------------
    | School
    |--------------------------------------------------------------------------
    */
    'school_name' => env('SCHOOL_NAME', 'Cawayan National High School'),
    'school_location' => env('SCHOOL_LOCATION', 'Bongabong, Oriental Mindoro'),

    /*
    |--------------------------------------------------------------------------
    | Two-factor authentication for staff and admin
    |--------------------------------------------------------------------------
    | When true, staff and admin accounts can only reach the records portals
    | after they confirmed two-factor authentication. Keep true in production.
    */
    'require_staff_2fa' => env('REQUIRE_STAFF_2FA', true),

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    | Minimum similarity (0 to 1) for a typo-tolerant name match, and the
    | maximum number of candidates the database returns before PHP ranks them.
    */
    'search' => [
        'min_score' => 0.6,
        'candidate_limit' => 300,
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Oldest school year with records (the school keeps records back to 1999).
    |--------------------------------------------------------------------------
    */
    'first_school_year' => 1999,
];
