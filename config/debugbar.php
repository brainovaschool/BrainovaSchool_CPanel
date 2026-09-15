<?php

/*
 * Debugbar auto-injects its floating toolbar HTML/CSS/JS into every response,
 * including plain error messages returned by these AI Helper endpoints — that's
 * why a short "please try again" message was showing up wrapped in a huge
 * block of debugbar boilerplate. Excluding these routes keeps their responses
 * clean for the fetch()-based JS that reads them directly, without touching
 * Debugbar anywhere else in the admin dashboard.
 */
return [
    'except' => [
        'ai-help-teacher/*',
        'student-panel-ai-help/*',
        'homeschool-ai-preview*',
        'demo-class*',
    ],
];
