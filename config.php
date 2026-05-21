<?php
return [
    'storage' => getenv('APP_STORAGE') ?: 'mysql',
    'site_name' => 'Tanösvény Kalauz',
    'owner_address' => '6000 Kecskemét, Liszt Ferenc utca 19.',
    'owner_email' => 'info@tanosveny-kalauz.hu',
    'db' => [
        'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=zap1380365_;charset=utf8mb4',
        'user' => getenv('DB_USER') ?: 'fhsadam',
        'pass' => getenv('DB_PASS') ?: 'Fhsadam123!',
    ],
    'upload_dir' => __DIR__ . '/public/uploads',
    'upload_url' => 'public/uploads',
];
