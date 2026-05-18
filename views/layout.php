<?php
function render_layout(string $title, string $content, array $config): void {
    $user = current_user();
    $menus = [
        ['fooldal','Főoldal'], ['kepek','Képek'], ['kapcsolat','Kapcsolat'], ['crud','CRUD']
    ];
    if ($user) $menus[] = ['uzenetek','Üzenetek'];
    $menus[] = $user ? ['kilepes','Kilépés'] : ['belepes','Bejelentkezés'];
    $current = $_GET['oldal'] ?? 'fooldal';
    $flash = flash();
    ?><!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=h($title)?> - <?=h($config['site_name'])?></title>
    <link rel="stylesheet" href="public/assets/css/style.css">
    <script src="public/assets/js/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <a class="brand" href="?oldal=fooldal" aria-label="Főoldal"><span class="brand-mark">↟</span> <?=h($config['site_name'])?></a>
        <p class="login-state"><?= $user ? 'Bejelentkezett: '.h($user['csaladi_nev'].' '.$user['utonev'].' ('.$user['login'].')') : 'Nincs bejelentkezett felhasználó' ?></p>
    </div>
    <nav class="main-nav" aria-label="Főmenü">
        <?php foreach($menus as [$key,$label]): ?>
            <a class="<?= $current===$key ? 'active' : '' ?>" href="?oldal=<?=h($key)?>"><?=h($label)?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main class="container">
    <?php if($flash): ?><div class="alert <?=h($flash['type'])?>"><?=h($flash['message'])?></div><?php endif; ?>
    <?=$content?>
</main>
<footer class="footer">
    <p><?=date('Y')?> &copy; <?=h($config['site_name'])?> - tanösvény adatbázis, PHP front-controller mintával.</p>
</footer>
</body>
</html><?php
}

