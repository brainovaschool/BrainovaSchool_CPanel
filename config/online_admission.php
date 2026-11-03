<?php

return [
    // online_admission_programs() reads Website Setup → Programs →
    // Categories directly now, so this list is only a fallback for an
    // empty/unreachable categories table - it's no longer what visitors
    // actually see as long as that table has active categories in it.
    'programs' => [
        'Homeschooling',
        'Academic Support',
        'Electives & Enrichment',
        'Social Clubs',
    ],
];
