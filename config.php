<?php
return [
    // Hostingon hagyja 'mysql' értéken. Helyi bemutatóhoz állítsa az APP_STORAGE környezeti változót 'json'-ra.
    'storage' => getenv('APP_STORAGE') ?: 'mysql',
    'site_name' => 'Tanösvény Kalauz',
    'owner_address' => '6000 Kecskemét, Liszt Ferenc utca 19.',
    'owner_email' => 'info@tanosveny-kalauz.hu',
    'db' => [
        'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=tanosveny;charset=utf8mb4',
        'user' => getenv('DB_USER') ?: 'adatbazis_felhasznalo',
        'pass' => getenv('DB_PASS') ?: 'adatbazis_jelszo',
    ],
    'upload_dir' => __DIR__ . '/public/uploads',
    'upload_url' => 'public/uploads',
];
