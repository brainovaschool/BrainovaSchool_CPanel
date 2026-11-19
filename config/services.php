<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN')
    ],

    // B2 (Backblaze) credentials are deliberately NOT cached here - see
    // App\Services\B2Storage::credential(). This file's own compiled copy
    // went stale on this host, so that class reads env() directly instead.

    'assessment_system' => [
        // Signs the login handoff token (see AssessmentSystemController).
        // Must also be pasted into the Google Apps Script's own Script
        // Properties once Step 5 adds server-side verification there -
        // until then it only shapes the token, nothing checks it yet.
        'auth_secret' => env('ASSESSMENT_SYSTEM_AUTH_SECRET'),
        // Where the built brainova-demo.html is actually hosted. Defaults
        // to the copy build.js writes straight into this app's own
        // public/ folder - override only if you ever host it elsewhere.
        'url' => env('ASSESSMENT_SYSTEM_URL', '/assessment-system/brainova-demo.html'),
        // The Google Apps Script web app URL (NOT the shared secret -
        // that one stays only in the app's own Settings page). Passed
        // along on every login so a brand-new device gets pointed at
        // the right Sheet automatically, without anyone having to type
        // it in by hand. See AssessmentSystemController::launch().
        'sheets_url' => env('ASSESSMENT_SYSTEM_SHEETS_URL'),
    ],

];
