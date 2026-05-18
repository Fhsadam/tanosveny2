# Tanösvény Kalauz - Web-programozás 1 beadandó

Komplett PHP/HTML/CSS/JavaScript alkalmazás a `Tanösvény` adatbázishoz.

## Tartalom

- A kötelező 7a/2 front-controller kiindulására épülő `index.php` (`oldal` GET paraméter, `function_exists`, `call_user_func`)
- Reszponzív HTML5 felület, vízszintes menü
- Dinamikus menü: Belépés/Kilépés, Üzenetek csak belépve
- Regisztráció, bejelentkezés, kijelentkezés
- Főoldal saját videóval, YouTube videóval, Google térképpel
- Képgaléria, belépéshez kötött képfeltöltéssel
- Kapcsolati űrlap kliensoldali JavaScript és szerveroldali PHP ellenőrzéssel
- Üzenetek oldal, belépve, fordított időrendben
- CRUD alkalmazás a `ut` táblára
- MySQL import SQL a megadott `np.txt`, `telepules.txt`, `ut.txt` állományokból

## Telepítés tárhelyen

1. Hozzon létre MySQL adatbázist, például `tanosveny` néven.
2. Importálja a `database/schema.sql` fájlt phpMyAdminban.
3. A `config.php` fájlban állítsa be az adatbázis kapcsolatot:
   - `dsn`: `mysql:host=localhost;dbname=ADATBAZIS_NEVE;charset=utf8mb4`
   - `user`: adatbázis-felhasználó
   - `pass`: adatbázis-jelszó
4. Töltse fel a teljes mappát a tárhely webgyökerébe.
5. A `public/uploads` mappa legyen írható a webszerver számára.

## Teszt belépés

- Login: `admin`
- Jelszó: `Admin123!`

Regisztráció után a rendszer szándékosan nem lépteti be automatikusan az új felhasználót, a feladatkiírás szerint.

## Helyi bemutató adatbázis nélkül

A mellékelt JSON demó tárolóval akkor is kipróbálható, ha nincs telepített MySQL/PDO driver:

```bash
cd tanosveny_app
APP_STORAGE=json php -S 127.0.0.1:8000
```

Majd böngészőben: `http://127.0.0.1:8000`

## Beadási teendők

A feladatkiírás szerint a kódot publikus GitHub repositoryba kell feltölteni legalább 5, időben elkülönülő commitként, az alkalmazást pedig internetes tárhelyen kell működtetni. A dokumentációban a saját GitHub URL-t, weboldal URL-t és tárhelybelépési adatokat ki kell cserélni a saját adatokra.


## Fontos javítás a tanári visszajelzés alapján

Ez a változat már nem új, saját routerrel indul, hanem a kötelező `7a - PHP Front-controller tervezési minta => 2. Megoldás` szerkezetét követi:

```php
$oldal = $_GET['oldal'] ?? 'fooldal';
if (function_exists($oldal)) {
    call_user_func($oldal);
} else {
    call_user_func('fooldal');
}
```

A tényleges oldalfüggvények a `controllers/oldalak.php` fájlban vannak: `fooldal()`, `kepek()`, `kapcsolat()`, `crud()`, `belepes()`, `kilepes()`, `uzenetek()`.
