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
    | Public details of the school, shown on the home page, in emails and on PDFs.
    | Source: the school's official Facebook page (contact info) and Republic Act
    | No. 8431 (1997), which created the school. The school ID is the number in
    | the school's DepEd email address. The school office must confirm every value.
    */
    'school' => [
        'name' => env('SCHOOL_NAME', 'Cawayan National High School'),
        'short_name' => env('SCHOOL_SHORT_NAME', 'Cawayan NHS'),
        'school_id' => env('SCHOOL_ID', '301624'),
        'address' => env('SCHOOL_ADDRESS', 'Cawayan, Bongabong, Oriental Mindoro 5211'),
        'phone' => env('SCHOOL_PHONE', '0963 783 4433'),
        'email' => env('SCHOOL_EMAIL', '301624@deped.gov.ph'),
        'district' => env('SCHOOL_DISTRICT', 'Bongabong South District'),
        'facebook' => env('SCHOOL_FACEBOOK', 'https://www.facebook.com/cawayannhs1998/'),
        'established_by' => 'Republic Act No. 8431 (1997)',
    ],

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
