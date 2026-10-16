<?php

return [
    // Explicit selection: a Firestore outage never silently writes to SQLite.
    'store' => env('SCHOOL_STORE', 'firestore'),
    'firebase_project' => env('FIREBASE_PROJECT_ID'),
    'firebase_database' => env('FIREBASE_DATABASE_ID', '(default)'),
    'firebase_credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase-service-account.json')),
    'firebase_credentials_json_base64' => env('FIREBASE_CREDENTIALS_JSON_BASE64'),
    'firebase_ca_bundle' => env('FIREBASE_CA_BUNDLE'),
    'whatsapp_group_url' => env('WHATSAPP_GROUP_URL', 'https://chat.whatsapp.com/CxsxIG5MnIYErggxZ8VkdZ?mode=gi_t'),
    'address' => env('SCHOOL_ADDRESS', 'Jl. Adhiyaksa No.9, Kacang Pedang, Kec. Gerunggang, Kota Pangkal Pinang'),
    'latitude' => (float) env('SCHOOL_LATITUDE', -2.1256678),
    'longitude' => (float) env('SCHOOL_LONGITUDE', 106.1037412),
    'map_url' => env('SCHOOL_MAP_URL', 'https://www.google.com/maps/place/Universitas+Pertiba+-+Uniper,+Pangkalpinang,+Bangka+Belitung/@-2.1256678,106.0992351,713m/data=!3m1!1e3!4m10!1m2!2m1!1spertiba!3m6!1s0x2e22c125a0f4c45f:0x61d9605e6f5c65f1!8m2!3d-2.1256678!4d106.1037412!15sCgdwZXJ0aWJhkgEKdW5pdmVyc2l0eeABAA!16s%2Fg%2F11b6g8vnm2?entry=ttu&g_ep=EgoyMDI2MTAxMy4wIKXMDSoASAFQAw%3D%3D'),
];
