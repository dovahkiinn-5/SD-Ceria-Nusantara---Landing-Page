<?php

return [
    // Explicit selection: a Firestore outage never silently writes to SQLite.
    'store' => env('SCHOOL_STORE', 'firestore'),
    'firebase_project' => env('FIREBASE_PROJECT_ID'),
    'firebase_database' => env('FIREBASE_DATABASE_ID', '(default)'),
    'firebase_credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase-service-account.json')),
    'firebase_credentials_json_base64' => env('FIREBASE_CREDENTIALS_JSON_BASE64'),
    'firebase_ca_bundle' => env('FIREBASE_CA_BUNDLE'),
];
