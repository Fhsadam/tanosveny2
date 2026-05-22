<?php

session_start();
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/models/Repository.php';
require_once __DIR__ . '/views/layout.php';

try {
    $repo = new Repository($config);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Adatbázis kapcsolat hiba</h1><p>Ellenőrizze a config.php adatbázis beállításait, vagy helyi kipróbáláshoz indítsa így: <code>APP_STORAGE=json php -S localhost:8000</code></p><pre>'.h($e->getMessage()).'</pre>';
    exit;
}

require_once __DIR__ . '/controllers/oldalak.php';

$oldal = $_GET['oldal'] ?? 'fooldal';
$oldal = preg_replace('/[^a-zA-Z0-9_]/', '', $oldal);

if (function_exists($oldal)) {
    call_user_func($oldal);
} else {
    call_user_func('fooldal');
}
