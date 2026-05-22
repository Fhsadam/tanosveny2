<?php
// 7a - PHP Front-controller tervezési minta => 2. Megoldás alapján: az index.php az oldal nevű GET paraméter alapján call_user_func-kal hívja ezeket a függvényeket.
function validate_trail(array $in): array {
    $errors=[];
    $nev=trim($in['nev'] ?? '');
    $hossz=str_replace(',','.',trim($in['hossz'] ?? ''));
    $allomas=trim($in['allomas'] ?? '');
    $ido=str_replace(',','.',trim($in['ido'] ?? ''));
    $telepulesid=(int)($in['telepulesid'] ?? 0);
    if (s_len($nev) < 3) $errors[]='A tanösvény neve legalább 3 karakter legyen.';
    if (!is_numeric($hossz) || (float)$hossz <= 0) $errors[]='A hossz pozitív szám legyen.';
    if (!ctype_digit($allomas) || (int)$allomas <= 0) $errors[]='Az állomások száma pozitív egész legyen.';
    if (!is_numeric($ido) || (float)$ido <= 0) $errors[]='A bejárási idő pozitív szám legyen.';
    if ($telepulesid <= 0) $errors[]='Válasszon települést.';
    return [$errors, ['nev'=>$nev,'hossz'=>(float)$hossz,'allomas'=>(int)$allomas,'ido'=>(float)$ido,'vezetes'=>isset($in['vezetes'])?1:0,'telepulesid'=>$telepulesid]];
}

function page_home(Repository $repo, array $config): string {
    $s=$repo->stats();
    ob_start(); ?>
<section class="hero">
    <div>
        <p class="eyebrow">Magyarországi tanösvények adatbázisa</p>
        <h1>Fedezze fel a tanösvényeket nemzeti parkok és települések szerint</h1>
        <p>A honlap a kiválasztott tanösvény adatbázisra épül. Kereshető útvonalak, képgaléria, kapcsolatfelvétel és teljes CRUD kezelés készült hozzá.</p>
        <div class="hero-actions"><a class="button" href="?oldal=crud">Tanösvények böngészése</a><a class="button secondary" href="?oldal=kepek">Képgaléria</a></div>
    </div>
    <div class="hero-card">
        <strong><?=h($s['utak'])?></strong><span>tanösvény</span>
        <strong><?=h($s['telepulesek'])?></strong><span>település</span>
        <strong><?=h($s['parkok'])?></strong><span>nemzeti park igazgatóság</span>
        <strong><?=num($s['hossz'])?> km</strong><span>összesített útvonalhossz</span>
    </div>
</section>
<section class="grid two">
    <article class="card"><h2>Helyi videó</h2><p>Rövid, 5 másodpercnél rövidebb bemutatóvideó a saját könyvtárból.</p><video controls muted preload="metadata" src="public/assets/video/tanosveny-rovid.mp4"></video></article>
    <article class="card"><h2>Szolgáltatói videó</h2><p>Külső videó beágyazása YouTube-ról.</p><div class="video-frame"><iframe src="https://www.youtube.com/embed/RWuACM-KSvs" title="Kiskunsági Nemzeti Park videó" allowfullscreen></iframe></div></article>
</section>
<section class="card"><h2>Fizikai cím és térkép</h2><p>A bemutató oldal kapcsolati helyszíne: <?=h($config['owner_address'])?>.</p><iframe class="map" loading="lazy" src="https://maps.google.com/maps?q=6000%20Kecskem%C3%A9t%2C%20Liszt%20Ferenc%20utca%2019&t=&z=15&ie=UTF8&iwloc=&output=embed" title="Google térkép"></iframe></section>
<?php return ob_get_clean(); }

function page_gallery(Repository $repo, array $config): string {
    require_csrf(); $errors=[];
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!is_logged_in()) { flash('Képfeltöltést csak bejelentkezett felhasználó végezhet.', 'error'); redirect_to('?oldal=kepek'); }
        $title=trim($_POST['title'] ?? '');
        if (s_len($title)<3) $errors[]='A kép címe legalább 3 karakter legyen.';
        if (empty($_FILES['image']['name'])) $errors[]='Válasszon képfájlt.';
        if (!$errors) {
            $tmp=$_FILES['image']['tmp_name']; $mime=mime_content_type($tmp); $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!isset($allowed[$mime])) $errors[]='Csak JPG, PNG, WEBP vagy GIF tölthető fel.';
            elseif ($_FILES['image']['size'] > 3*1024*1024) $errors[]='A kép legfeljebb 3 MB lehet.';
            else {
                if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
                $filename='kep_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$allowed[$mime];
                $target=$config['upload_dir'].'/'.$filename;
                if (!move_uploaded_file($tmp, $target)) $errors[]='A kép mentése nem sikerült.';
                else { $repo->saveImage(['title'=>$title,'filename'=>$config['upload_url'].'/'.$filename,'uploaded_by'=>current_user()['login']]); flash('A kép feltöltése sikerült.'); redirect_to('?oldal=kepek'); }
            }
        }
    }
    $images=$repo->listImages(); ob_start(); ?>
<h1>Képek és képgaléria</h1>
<p class="lead">A galéria a tanösvények hangulatát mutatja be. Új képet csak bejelentkezett felhasználó tölthet fel.</p>
<?php if($errors): ?><div class="alert error"><?=h(implode(' ', $errors))?></div><?php endif; ?>
<?php if(is_logged_in()): ?><section class="card"><h2>Új kép feltöltése</h2><form method="post" enctype="multipart/form-data" class="form"><?=csrf_field()?><label>Kép címe <input type="text" name="title"></label><label>Képfájl <input type="file" name="image" accept="image/*"></label><button class="button">Feltöltés</button></form></section><?php endif; ?>
<section class="gallery"><?php foreach($images as $img): $src = str_starts_with($img['filename'], 'assets/') ? 'public/'.$img['filename'] : $img['filename']; ?><figure><img src="<?=h($src)?>" alt="<?=h($img['title'])?>"><figcaption><strong><?=h($img['title'])?></strong><span>Feltöltő: <?=h($img['uploaded_by'])?></span></figcaption></figure><?php endforeach; ?></section>
<?php return ob_get_clean(); }

function page_contact(Repository $repo): string {
    require_csrf(); $errors=[]; $old=['name'=>'','email'=>'','subject'=>'','message'=>''];
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        foreach($old as $k=>$_) $old[$k]=trim($_POST[$k] ?? '');
        if (s_len($old['name'])<2) $errors[]='A név legalább 2 karakter legyen.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[]='Érvényes e-mail címet adjon meg.';
        if (s_len($old['subject'])<3) $errors[]='A tárgy legalább 3 karakter legyen.';
        if (s_len($old['message'])<10) $errors[]='Az üzenet legalább 10 karakter legyen.';
        if (!$errors) {
            $old['sender_user']=is_logged_in()?full_name(current_user()).' ('.current_user()['login'].')':'Vendég';
            $id=$repo->saveMessage($old); redirect_to('?oldal=kapcsolat_elkuldve&id='.$id);
        }
    }
    ob_start(); ?>
<h1>Kapcsolat</h1><p class="lead">Az űrlap kliensoldali JavaScript és szerveroldali PHP ellenőrzést is kapott. A HTML-kódban nincs kötelező mezős ellenőrzés.</p>
<?php if($errors): ?><div class="alert error"><?=h(implode(' ', $errors))?></div><?php endif; ?>
<form method="post" class="form card" id="contactForm" novalidate><?=csrf_field()?><label>Név <input type="text" name="name" value="<?=h($old['name'])?>"></label><label>E-mail <input type="text" name="email" value="<?=h($old['email'])?>"></label><label>Tárgy <input type="text" name="subject" value="<?=h($old['subject'])?>"></label><label>Üzenet <textarea name="message" rows="7"><?=h($old['message'])?></textarea></label><div class="client-errors" aria-live="polite"></div><button class="button">Üzenet küldése</button></form>
<?php return ob_get_clean(); }

function page_contact_success(Repository $repo): string { $m=$repo->getMessage((int)($_GET['id'] ?? 0)); ob_start(); if(!$m): ?><h1>Üzenet nem található</h1><?php else: ?><h1>Elküldött üzenet</h1><div class="card"><dl class="details"><dt>Küldő</dt><dd><?=h($m['name'])?> (<?=h($m['sender_user'])?>)</dd><dt>E-mail</dt><dd><?=h($m['email'])?></dd><dt>Tárgy</dt><dd><?=h($m['subject'])?></dd><dt>Üzenet</dt><dd><?=nl2br(h($m['message']))?></dd><dt>Küldés ideje</dt><dd><?=h($m['created_at'] ?? date('Y-m-d H:i:s'))?></dd></dl></div><?php endif; return ob_get_clean(); }

function page_messages(Repository $repo): string { if(!is_logged_in()) { flash('Az üzenetek megtekintéséhez be kell jelentkezni.', 'error'); redirect_to('?oldal=belepes'); } $rows=$repo->listMessages(); ob_start(); ?><h1>Üzenetek</h1><p class="lead">Az adatbázisban tárolt üzenetek fordított időrendben jelennek meg.</p><div class="table-wrap"><table><thead><tr><th>Idő</th><th>Küldő</th><th>Név</th><th>E-mail</th><th>Tárgy</th><th>Üzenet</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=h($r['created_at'])?></td><td><?=h($r['sender_user'])?></td><td><?=h($r['name'])?></td><td><?=h($r['email'])?></td><td><?=h($r['subject'])?></td><td><?=h(s_trimwidth($r['message'],90))?></td></tr><?php endforeach; ?></tbody></table></div><?php return ob_get_clean(); }

function trail_form(Repository $repo, array $trail, string $mode): string { $telepulesek=$repo->listTelepulesek(); ob_start(); ?><form method="post" class="form card"><?=csrf_field()?><label>Tanösvény neve <input type="text" name="nev" value="<?=h($trail['nev'] ?? '')?>"></label><div class="form-grid"><label>Hossz (km) <input type="text" name="hossz" value="<?=h($trail['hossz'] ?? '')?>"></label><label>Állomások <input type="text" name="allomas" value="<?=h($trail['allomas'] ?? '')?>"></label><label>Idő (óra) <input type="text" name="ido" value="<?=h($trail['ido'] ?? '')?>"></label><label>Település <select name="telepulesid"><option value="">-- válasszon --</option><?php foreach($telepulesek as $t): ?><option value="<?=h($t['id'])?>" <?=selected($trail['telepulesid'] ?? '', $t['id'])?>><?=h($t['nev'])?></option><?php endforeach; ?></select></label></div><label class="check"><input type="checkbox" name="vezetes" value="1" <?=checked($trail['vezetes'] ?? 0)?>> Van szakvezetés</label><button class="button"><?= $mode==='szerkeszt'?'Mentés':'Létrehozás' ?></button><a class="button secondary" href="?oldal=crud">Mégse</a></form><?php return ob_get_clean(); }

function page_trails(Repository $repo, string $muvelet): string {
    if ($muvelet === 'letrehoz' || $muvelet === 'szerkeszt') {
        require_csrf(); $id=(int)($_GET['id'] ?? 0); $trail=$muvelet==='szerkeszt'?$repo->getTrail($id):['nev'=>'','hossz'=>'','allomas'=>'','ido'=>'','vezetes'=>0,'telepulesid'=>'']; if($muvelet==='szerkeszt' && !$trail) return '<h1>Nincs ilyen tanösvény</h1>';
        $errors=[]; if($_SERVER['REQUEST_METHOD']==='POST') { [$errors,$data]=validate_trail($_POST); if(!$errors) { if($muvelet==='szerkeszt') { $repo->updateTrail($id,$data); flash('A tanösvény módosítása sikerült.'); } else { $repo->createTrail($data); flash('Az új tanösvény létrejött.'); } redirect_to('?oldal=crud'); } $trail=array_merge($trail,$data ?? []); }
        ob_start(); ?><h1><?= $muvelet==='szerkeszt'?'Tanösvény szerkesztése':'Új tanösvény' ?></h1><?php if($errors): ?><div class="alert error"><?=h(implode(' ', $errors))?></div><?php endif; ?><?=trail_form($repo,$trail,$muvelet)?><?php return ob_get_clean();
    }
    if ($muvelet === 'torol') { require_csrf(); $id=(int)($_GET['id'] ?? 0); $trail=$repo->getTrail($id); if(!$trail) return '<h1>Nincs ilyen tanösvény</h1>'; if($_SERVER['REQUEST_METHOD']==='POST') { $repo->deleteTrail($id); flash('A tanösvény törölve lett.'); redirect_to('?oldal=crud'); } ob_start(); ?><h1>Törlés megerősítése</h1><div class="card"><p>Biztosan törli ezt a tanösvényt: <strong><?=h($trail['nev'])?></strong>?</p><form method="post"><?=csrf_field()?><button class="button danger">Igen, törlöm</button><a class="button secondary" href="?oldal=crud">Mégse</a></form></div><?php return ob_get_clean(); }
    $search=trim($_GET['q'] ?? ''); $npid=trim($_GET['npid'] ?? ''); $rows=$repo->listTrails($search,$npid,300); $nps=$repo->listNationalParks(); ob_start(); ?>
<h1>CRUD - tanösvények</h1><p class="lead">A tanösvény tábla teljes Create, Read, Update, Delete kezelést kapott. A lista településsel és nemzeti park igazgatósággal együtt jelenik meg.</p>
<form class="filters" method="get"><input type="hidden" name="oldal" value="crud"><label>Keresés <input type="search" name="q" value="<?=h($search)?>" placeholder="név, település, park"></label><label>Nemzeti park <select name="npid"><option value="">Mind</option><?php foreach($nps as $n): ?><option value="<?=h($n['id'])?>" <?=selected($npid,$n['id'])?>><?=h($n['nev'])?></option><?php endforeach; ?></select></label><button class="button">Szűrés</button><a class="button secondary" href="?oldal=crud&muvelet=letrehoz">Új tanösvény</a></form>
<div class="table-wrap"><table><thead><tr><th>Név</th><th>Település</th><th>Nemzeti park</th><th>Hossz</th><th>Állomás</th><th>Idő</th><th>Vezetés</th><th>Művelet</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=h($r['nev'])?></td><td><?=h($r['telepules'])?></td><td><?=h($r['nemzeti_park'])?></td><td><?=num($r['hossz'])?> km</td><td><?=h($r['allomas'])?></td><td><?=num($r['ido'])?> óra</td><td><?= $r['vezetes']?'igen':'nem' ?></td><td class="actions"><a href="?oldal=crud&muvelet=szerkeszt&id=<?=h($r['azon'])?>">Szerkesztés</a><a href="?oldal=crud&muvelet=torol&id=<?=h($r['azon'])?>">Törlés</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php return ob_get_clean(); }

function page_login(Repository $repo): string { require_csrf(); $errors=[]; $regErrors=[];
    if($_SERVER['REQUEST_METHOD']==='POST') {
        $form=$_POST['form'] ?? '';
        if($form==='login') { $login=trim($_POST['login'] ?? ''); $pass=$_POST['password'] ?? ''; $user=$repo->findUser($login); if($user && password_verify($pass,$user['password_hash'])) { $_SESSION['user']=['id'=>$user['id'],'csaladi_nev'=>$user['csaladi_nev'],'utonev'=>$user['utonev'],'login'=>$user['login']]; flash('Sikeres bejelentkezés.'); redirect_to('?oldal=fooldal'); } $errors[]='Hibás felhasználónév vagy jelszó.'; }
        if($form==='register') { $data=['csaladi_nev'=>trim($_POST['csaladi_nev'] ?? ''),'utonev'=>trim($_POST['utonev'] ?? ''),'login'=>trim($_POST['reg_login'] ?? '')]; $pass=$_POST['reg_password'] ?? ''; if(s_len($data['csaladi_nev'])<2) $regErrors[]='A családi név rövid.'; if(s_len($data['utonev'])<2) $regErrors[]='Az utónév rövid.'; if(!preg_match('/^[a-zA-Z0-9_.-]{4,40}$/',$data['login'])) $regErrors[]='A login 4-40 karakter, betű, szám, pont, kötőjel vagy aláhúzás lehet.'; if(s_len($pass)<8) $regErrors[]='A jelszó legalább 8 karakter legyen.'; if($repo->findUser($data['login'])) $regErrors[]='Ez a login már foglalt.'; if(!$regErrors) { $data['password_hash']=password_hash($pass, PASSWORD_DEFAULT); $repo->createUser($data); flash('Regisztráció sikeres. Most jelentkezzen be.'); redirect_to('?oldal=belepes'); } }
    }
    ob_start(); ?><h1>Bejelentkezés és regisztráció</h1><div class="grid two"><section class="card"><h2>Belépés</h2><?php if($errors): ?><div class="alert error"><?=h(implode(' ', $errors))?></div><?php endif; ?><form method="post" class="form"><?=csrf_field()?><input type="hidden" name="form" value="login"><label>Login név <input type="text" name="login"></label><label>Jelszó <input type="password" name="password"></label><button class="button">Belépés</button><p class="small">Teszt felhasználó: <strong>admin</strong> / <strong>Admin123!</strong></p></form></section><section class="card"><h2>Regisztráció</h2><?php if($regErrors): ?><div class="alert error"><?=h(implode(' ', $regErrors))?></div><?php endif; ?><form method="post" class="form"><?=csrf_field()?><input type="hidden" name="form" value="register"><label>Családi név <input type="text" name="csaladi_nev"></label><label>Utónév <input type="text" name="utonev"></label><label>Login név <input type="text" name="reg_login"></label><label>Jelszó <input type="password" name="reg_password"></label><button class="button">Regisztráció</button><p class="small">Regisztráció után a rendszer nem lépteti be automatikusan a felhasználót.</p></form></section></div><?php return ob_get_clean(); }


function fooldal(): void { global $repo, $config; render_layout('Főoldal', page_home($repo, $config), $config); }
function kepek(): void { global $repo, $config; render_layout('Képek', page_gallery($repo, $config), $config); }
function kapcsolat(): void { global $repo, $config; render_layout('Kapcsolat', page_contact($repo), $config); }
function kapcsolat_elkuldve(): void { global $repo, $config; render_layout('Elküldött üzenet', page_contact_success($repo), $config); }
function uzenetek(): void { global $repo, $config; render_layout('Üzenetek', page_messages($repo), $config); }
function crud(): void { global $repo, $config; $muvelet = $_GET['muvelet'] ?? 'index'; render_layout('CRUD', page_trails($repo, $muvelet), $config); }
function belepes(): void { global $repo, $config; render_layout('Bejelentkezés', page_login($repo), $config); }
function kilepes(): void { session_destroy(); session_start(); flash('Sikeres kijelentkezés.'); redirect_to('?oldal=fooldal'); }
