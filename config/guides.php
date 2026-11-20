<?php

/*
|--------------------------------------------------------------------------
| Step-by-step guides taught by the help assistant
|--------------------------------------------------------------------------
| Each step highlights an element (data-tour="..." in the Vue pages), says one
| short sentence in English and Tagalog, and may open a page first (url).
| `audience` says who can use it: visitor (not signed in), student (learner,
| parent, alumni), staff, admin. A visitor who asks for a signed-in guide is first
| shown where to log in.
*/

return [

    'register' => [
        'audience' => ['visitor'],
        'steps' => [
            ['url' => '/', 'target' => 'home-register', 'text' => 'Click Register to create your account.', 'text_tl' => 'I-click ang Register para gumawa ng account.'],
            ['url' => '/register', 'target' => 'auth-form', 'text' => 'Type your name, email and a password, then press Create account.', 'text_tl' => 'Ilagay ang pangalan, email at password, tapos pindutin ang Create account.'],
        ],
    ],

    'login' => [
        'audience' => ['visitor'],
        'steps' => [
            ['url' => '/', 'target' => 'home-login', 'text' => 'Click Log in.', 'text_tl' => 'I-click ang Log in.'],
            ['url' => '/login', 'target' => 'auth-form', 'text' => 'Type your email and password, then press Log in.', 'text_tl' => 'Ilagay ang email at password, tapos pindutin ang Log in.'],
        ],
    ],

    'forgot_password' => [
        'audience' => ['visitor'],
        'steps' => [
            ['url' => '/login', 'target' => 'forgot-link', 'text' => 'Click "Forgot your password?" and type your email. You will get a link to set a new password.', 'text_tl' => 'I-click ang "Forgot your password?" at ilagay ang email. Makakatanggap ka ng link para sa bagong password.'],
        ],
    ],

    'link_record' => [
        'audience' => ['student'],
        'steps' => [
            ['url' => '/my-records', 'target' => 'link-form', 'text' => "Type the learner's LRN and birthdate, choose who you are, then press Send request. The registrar will approve it.", 'text_tl' => 'Ilagay ang LRN at birthdate ng learner, piliin kung sino ka, tapos pindutin ang Send request. Aaprubahan ito ng registrar.'],
        ],
    ],

    'request_document' => [
        'audience' => ['student'],
        'steps' => [
            ['url' => '/my-requests', 'target' => 'new-request-btn', 'text' => 'Press New request.', 'text_tl' => 'Pindutin ang New request.'],
            ['url' => '/my-requests/create', 'target' => 'request-form', 'text' => 'Choose the learner and the document, write the purpose, then press Send request.', 'text_tl' => 'Piliin ang learner at ang document, isulat ang dahilan, tapos pindutin ang Send request.'],
        ],
    ],

    'track_request' => [
        'audience' => ['student'],
        'steps' => [
            ['url' => '/my-requests', 'target' => 'requests-table', 'text' => 'Your requests are here. Click a request number to see every step and to download the document when it is released.', 'text_tl' => 'Nandito ang mga request mo. I-click ang request number para makita ang bawat hakbang at ma-download ang document kapag na-release na.'],
        ],
    ],

    'enroll' => [
        'audience' => ['student'],
        'steps' => [
            ['url' => '/my-enrollments', 'target' => 'enroll-new-btn', 'text' => 'Press New enrollment form.', 'text_tl' => 'Pindutin ang New enrollment form.'],
            ['url' => '/my-enrollments/create', 'target' => 'enroll-form', 'text' => "Fill in the learner's details, upload the documents, then press Send form.", 'text_tl' => 'Punan ang detalye ng learner, i-upload ang mga document, tapos pindutin ang Send form.'],
        ],
    ],

    'search_student' => [
        'audience' => ['staff', 'admin'],
        'steps' => [
            ['url' => '/staff/students', 'target' => 'student-search', 'text' => 'Type an LRN or a name here. Small spelling mistakes are fine.', 'text_tl' => 'Ilagay dito ang LRN o pangalan. Okay lang ang maliliit na mali sa spelling.'],
        ],
    ],

    'process_request' => [
        'audience' => ['staff', 'admin'],
        'steps' => [
            ['url' => '/staff/requests', 'target' => 'requests-table', 'text' => 'Open a request, then use Start review, Approve and Release.', 'text_tl' => 'Buksan ang request, tapos gamitin ang Start review, Approve at Release.'],
        ],
    ],

    'approve_enrollment' => [
        'audience' => ['staff', 'admin'],
        'steps' => [
            ['url' => '/staff/enrollments', 'target' => 'enrollments-table', 'text' => 'Open a form, check the documents, then Approve (you can pick a section) or Reject.', 'text_tl' => 'Buksan ang form, tingnan ang mga document, tapos i-Approve (puwedeng pumili ng section) o i-Reject.'],
        ],
    ],

    'add_section' => [
        'audience' => ['staff', 'admin'],
        'steps' => [
            ['url' => '/staff/sections', 'target' => 'section-add', 'text' => 'Press Add section in the grade you want.', 'text_tl' => 'Pindutin ang Add section sa grade na gusto mo.'],
        ],
    ],

    'digitize' => [
        'audience' => ['staff', 'admin'],
        'steps' => [
            ['url' => '/staff/ocr', 'target' => 'ocr-upload-btn', 'text' => 'Press Upload a scan, choose the picture, then check every field before saving.', 'text_tl' => 'Pindutin ang Upload a scan, piliin ang larawan, tapos suriin ang bawat field bago i-save.'],
        ],
    ],
];
