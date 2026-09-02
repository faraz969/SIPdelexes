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

    'erp' => [
        'base_url' => env('ERP_BASE_URL', 'http://localhost:8000/api'),
        'api_key' => env('ERP_API_KEY', ''),
        'api_secret' => env('ERP_API_SECRET', ''),
        'auth_type' => env('ERP_AUTH_TYPE', 'token'), // 'token' for ERPNext standard API
        'default_program' => env('ERP_DEFAULT_PROGRAM', 'General'),
        // When null, ERPIntegrationService falls back to current academic year from Admin → Academic Year.
        'default_academic_term' => env('ERP_DEFAULT_ACADEMIC_TERM'),
        'program_mapping' => [], // [sip_program_id => erp_program_name] if names differ
        // If your ERPNext host routes all API calls to one URL and expects "cmd" in body, set to true
        'use_cmd_endpoint' => env('ERP_USE_CMD_ENDPOINT', false),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],

    'accounts' => [
        'email' => env('ACCOUNTS_EMAIL', 'accounts@delexesuniversity.edu.gh'),
    ],

    'registrar' => [
        'email' => env('REGISTRAR_EMAIL'),
    ],

];
