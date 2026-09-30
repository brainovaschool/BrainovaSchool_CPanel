<?php

return [
    // Brainova is an online school — these are the paths a family can apply
    // for. Kept in sync with the four official program names (see the
    // website fix list, decision D3) and with the ProgramCategory rows in
    // Website Setup → Programs → Categories — a name changed in one place
    // and not the other is exactly how "Tutoring" vs "Academic Support"
    // drifted apart before. "Online Short Courses" was dropped: it wasn't
    // one of the four names and had no program page behind it.
    'programs' => [
        'Homeschooling',
        'Academic Support',
        'Electives & Enrichment',
        'Social Clubs',
    ],
];
