<?php
/*
 * Portfolio ohne CMS
 * ------------------
 * Werke:   Bilder und je eine gleichnamige .txt-Datei in den Ordner "werke" legen.
 * Seiten:  Vita, Ausstellungen und Links stehen als einfache Textdateien im Ordner "texte".
 * Mehr dazu in README.md
 *
 * Benötigt PHP 8.0 oder neuer (mit GD für die Vorschaubilder).
 */

// ---------- Einstellungen: hier anpassen ----------
$titel           = 'Hans Georg Dornhege';
$untertitel      = 'Malerei · Zeichnung · Skulptur';
$zeigeUntertitel = false;                        // false = Untertitel ausblenden (Text bleibt hier stehen)
$werkeTitel      = 'Werke (Auswahl)';            // Überschrift über den Bildern; leer lassen = keine Überschrift
$copyright       = 'Pablo Dornhege';              // Name in der Fußzeile: © Jahr Name (leer = Titel von oben)
$email           = 'webmaster@dornhege.com';      // z. B. 'pablo@dornhege.com'
$ordner          = 'werke';                       // Ordner mit Bildern und Textdateien zu den Werken
$texte           = 'texte';                      // Ordner mit vita.txt, ausstellungen.txt und links.txt

// ---------- Ab hier muss nichts geändert werden ----------
const BILD_ENDUNGEN = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const GROESSEN      = ['t' => 700, 'l' => 2200]; // längste Seite in Pixel: Vorschau / Großansicht
const SEITEN        = [                          // Adresse => [Name im Menü, Datei, Art]
    'vita'          => ['Vita', 'vita.txt', 'zeitleiste'],
    'ausstellungen' => ['Ausstellungen', 'ausstellungen.txt', 'zeitleiste'],
    'links'         => ['Links', 'links.txt', 'links'],
];
const FELD_ALIAS    = [
    'title' => 'titel', 'name' => 'titel',
    'year' => 'jahr', 'datum' => 'jahr', 'entstehungsjahr' => 'jahr',
    'maße' => 'masse', 'masse' => 'masse', 'größe' => 'masse', 'groesse' => 'masse',
    'format' => 'masse', 'abmessungen' => 'masse', 'maß' => 'masse',
    'technik' => 'technik', 'material' => 'technik', 'medium' => 'technik',
    'beschreibung' => 'text', 'text' => 'text', 'notiz' => 'text', 'anmerkung' => 'text',
];

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Text mit Links der Form [Text](https://adresse), sonst alles maskiert. */
function inline(string $s): string
{
    return preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/u',
        fn($m) => '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>',
        h($s)
    );
}

function ist_bild(string $datei): bool
{
    return in_array(strtolower(pathinfo($datei, PATHINFO_EXTENSION)), BILD_ENDUNGEN, true);
}

/** "01_Abendlicht_am_See" wird zu "Abendlicht am See" */
function schoen(string $name): string
{
    $name = preg_replace('/^\d{1,3}[\s._-]+/', '', $name);
    return trim(str_replace('_', ' ', $name));
}

/** Liest eine Textdatei als UTF-8 (auch wenn sie aus dem Windows-Editor stammt). */
function lade_text(string $datei): string
{
    $inhalt = (string)file_get_contents($datei);
    $inhalt = preg_replace('/^\xEF\xBB\xBF/', '', $inhalt);
    if (!mb_check_encoding($inhalt, 'UTF-8')) {
        $inhalt = mb_convert_encoding($inhalt, 'UTF-8', 'Windows-1252');
    }
    return $inhalt;
}

/** Zeilen einer Seiten-Textdatei ohne Leerzeilen und ohne //-Notizen. */
function lese_zeilen(string $datei): array
{
    if (!is_file($datei)) {
        return [];
    }
    $out = [];
    foreach (preg_split('/\R/u', lade_text($datei)) as $z) {
        $z = trim($z);
        if ($z !== '' && !str_starts_with($z, '//')) {
            $out[] = $z;
        }
    }
    return $out;
}

/** Vita / Ausstellungen: "# Überschrift", "Jahr: Text" oder freier Absatz. */
function parse_zeitleiste(array $zeilen): array
{
    $out = [];
    foreach ($zeilen as $z) {
        if (preg_match('/^#+\s*(.+)$/u', $z, $m)) {
            $out[] = ['t' => 'h', 'text' => $m[1]];
        } elseif (preg_match('/^([^:]{1,25}?)\s*:\s+(.+)$/u', $z, $m) && preg_match('/\d/', $m[1])) {
            $out[] = ['t' => 'zeile', 'jahr' => $m[1], 'text' => $m[2]];
        } else {
            $out[] = ['t' => 'p', 'text' => $z];
        }
    }
    return $out;
}

/** Links: "Titel | Adresse | Zusatz", "# Überschrift" oder freier Text (z. B. Literatur). */
function parse_links(array $zeilen): array
{
    $out = [];
    foreach ($zeilen as $z) {
        if (preg_match('/^#+\s*(.+)$/u', $z, $m)) {
            $out[] = ['t' => 'h', 'text' => $m[1]];
            continue;
        }
        [$titel, $url, $zusatz] = array_pad(array_map('trim', explode('|', $z)), 3, '');
        if ($url === '' && preg_match('~^(https?://|www\.)\S+$~i', $titel)) { // nur eine Adresse
            [$url, $titel] = [$titel, ''];
        }
        if (preg_match('~^www\.~i', $url)) {
            $url = 'https://' . $url;
        }
        if ($url !== '' && preg_match('~^https?://[^\s]+$~i', $url)) {
            $host = preg_replace('/^www\./i', '', (string)parse_url($url, PHP_URL_HOST));
            $out[] = ['t' => 'link', 'titel' => $titel !== '' ? $titel : $host, 'url' => $url, 'zusatz' => $zusatz !== '' ? $zusatz : $host];
        } else {
            $out[] = ['t' => 'p', 'text' => $z];
        }
    }
    return $out;
}

function exif_ausrichtung(string $pfad): int
{
    if (!function_exists('exif_read_data')) {
        return 1;
    }
    $e = @exif_read_data($pfad);
    return (int)($e['Orientation'] ?? 1);
}

/** Erzeugt (und merkt sich) eine verkleinerte JPEG-Version. Gibt den Pfad zurück oder null. */
function verkleinert(string $pfad, int $max): ?string
{
    if (!function_exists('imagecreatetruecolor')) {
        return null;
    }
    $cache = __DIR__ . '/cache';
    if (!is_dir($cache) && !@mkdir($cache, 0755, true)) {
        return null;
    }
    $ziel = $cache . '/' . md5($pfad . '|' . filemtime($pfad) . '|' . $max) . '.jpg';
    if (is_file($ziel)) {
        return $ziel;
    }
    @ini_set('memory_limit', '1024M');
    $info = @getimagesize($pfad);
    if (!$info) {
        return null;
    }
    $quelle = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($pfad),
        IMAGETYPE_PNG  => @imagecreatefrompng($pfad),
        IMAGETYPE_GIF  => @imagecreatefromgif($pfad),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($pfad) : false,
        default        => false,
    };
    if (!$quelle) {
        return null;
    }
    if ($info[2] === IMAGETYPE_JPEG) {
        $drehung = [3 => 180, 6 => -90, 8 => 90][exif_ausrichtung($pfad)] ?? 0;
        if ($drehung) {
            $quelle = imagerotate($quelle, $drehung, 0) ?: $quelle;
        }
    }
    $w = imagesx($quelle);
    $hoehe = imagesy($quelle);
    $f = min(1, $max / max($w, $hoehe));
    $nw = max(1, (int)round($w * $f));
    $nh = max(1, (int)round($hoehe * $f));
    $bild = imagecreatetruecolor($nw, $nh);
    imagefill($bild, 0, 0, imagecolorallocate($bild, 255, 255, 255));
    imagecopyresampled($bild, $quelle, 0, 0, 0, 0, $nw, $nh, $w, $hoehe);
    imageinterlace($bild, true);
    $tmp = $ziel . '.' . getmypid() . '.tmp';
    if (!imagejpeg($bild, $tmp, 86) || !@rename($tmp, $ziel)) {
        @unlink($tmp);
        return null;
    }
    return $ziel;
}

/** Liest die gleichnamige .txt-Datei zu einem Bild: Zeilen der Form "Schlüssel: Wert". */
function lese_info(string $bild): array
{
    $stamm = preg_replace('/\.[^.]+$/', '', $bild);
    $txt = null;
    foreach (['.txt', '.TXT', '.Txt'] as $endung) {
        if (is_file($stamm . $endung)) {
            $txt = $stamm . $endung;
            break;
        }
    }
    if ($txt === null) {
        return [];
    }
    $felder = [];
    $aktuell = null;
    foreach (preg_split('/\R/u', lade_text($txt)) as $zeile) {
        if (preg_match('/^\s*(\p{L}[\p{L}\p{N} _-]{0,24})\s*:\s*(.*)$/u', $zeile, $m)) {
            $k = mb_strtolower(trim($m[1]));
            $k = FELD_ALIAS[$k] ?? $k;
            $felder[$k] = trim($m[2]);
            $aktuell = $k;
        } elseif (trim($zeile) !== '') {
            // Zeile ohne Schlüssel: gehört zum vorherigen Feld (oder ist freier Text)
            $aktuell ??= 'text';
            $felder[$aktuell] = isset($felder[$aktuell]) && $felder[$aktuell] !== ''
                ? $felder[$aktuell] . "\n" . trim($zeile)
                : trim($zeile);
        }
    }
    return $felder;
}

function sammle(string $verz, string $basis, string $katOrdner): array
{
    $namen = scandir($verz) ?: [];
    natcasesort($namen);
    $out = [];
    foreach ($namen as $n) {
        $p = $verz . DIRECTORY_SEPARATOR . $n;
        if ($n[0] === '.' || !is_file($p) || !ist_bild($p)) {
            continue;
        }
        $info = @getimagesize($p);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            continue;
        }
        [$bw, $bh] = $info;
        if ($info[2] === IMAGETYPE_JPEG && in_array(exif_ausrichtung($p), [5, 6, 7, 8], true)) {
            [$bw, $bh] = [$bh, $bw];
        }
        $f = lese_info($p);
        $meta = array_filter([$f['jahr'] ?? '', $f['masse'] ?? '', $f['technik'] ?? ''], fn($v) => $v !== '');
        $text = [];
        if (!empty($f['text'])) {
            $text[] = $f['text'];
        }
        foreach ($f as $k => $v) {
            if ($v !== '' && !in_array($k, ['titel', 'jahr', 'masse', 'technik', 'text'], true)) {
                $text[] = mb_convert_case($k, MB_CASE_TITLE) . ': ' . $v;
            }
        }
        $out[] = [
            'rel'   => str_replace(DIRECTORY_SEPARATOR, '/', substr($p, strlen($basis) + 1)),
            'v'     => filemtime($p),
            'ratio' => round($bw / $bh, 4),
            'titel' => ($f['titel'] ?? '') !== '' ? $f['titel'] : schoen(pathinfo($n, PATHINFO_FILENAME)),
            'jahr'  => $f['jahr'] ?? '',
            'meta'  => implode(' · ', $meta),
            'text'  => implode("\n", $text),
            'kat'   => $katOrdner === '' ? '' : schoen($katOrdner),
        ];
    }
    return $out;
}

// ---------- Bildauslieferung: ?i=Pfad&s=t|l ----------
$basis = realpath(__DIR__ . '/' . $ordner);

if (isset($_GET['i'])) {
    $rel = is_string($_GET['i']) ? $_GET['i'] : '';
    $pfad = ($basis && $rel !== '' && !str_contains($rel, "\0")) ? realpath($basis . '/' . $rel) : false;
    if (!$pfad || !str_starts_with($pfad, $basis . DIRECTORY_SEPARATOR) || !is_file($pfad) || !ist_bild($pfad)) {
        http_response_code(404);
        exit('Nicht gefunden');
    }
    $s = is_string($_GET['s'] ?? null) ? $_GET['s'] : 't';
    $datei = verkleinert($pfad, GROESSEN[$s] ?? GROESSEN['t']);
    if ($datei) {
        $mime = 'image/jpeg';
    } else {
        $datei = $pfad;
        $mime = @getimagesize($pfad)['mime'] ?? 'application/octet-stream';
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($datei));
    header('Cache-Control: public, max-age=2592000');
    readfile($datei);
    exit;
}

// ---------- Seiten (Vita, Ausstellungen, Links) einlesen ----------
$bloecke = [];
foreach (SEITEN as $key => [$label, $datei, $art]) {
    $zeilen = lese_zeilen(__DIR__ . '/' . $texte . '/' . $datei);
    $b = $art === 'links' ? parse_links($zeilen) : parse_zeitleiste($zeilen);
    if ($b) {
        $bloecke[$key] = $b;
    }
}
$aktiv = is_string($_GET['seite'] ?? null) ? $_GET['seite'] : 'werke';
if (!isset($bloecke[$aktiv])) {
    $aktiv = 'werke';
}

// ---------- Werke einlesen (nur auf der Startseite) ----------
$werke = [];
if ($aktiv === 'werke' && $basis) {
    $werke = sammle($basis, $basis, '');
    $unter = array_filter(
        scandir($basis) ?: [],
        fn($n) => $n[0] !== '.' && is_dir($basis . DIRECTORY_SEPARATOR . $n)
    );
    natcasesort($unter);
    foreach ($unter as $n) {
        $werke = array_merge($werke, sammle($basis . DIRECTORY_SEPARATOR . $n, $basis, $n));
    }
}
$rubriken = array_values(array_unique(array_filter(array_column($werke, 'kat'), fn($k) => $k !== '')));
$impressum = is_file(__DIR__ . '/impressum.html');
$seitentitel = $aktiv === 'werke' ? $titel : SEITEN[$aktiv][0] . ' · ' . $titel;
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= h($seitentitel) ?></title>
<meta name="description" content="<?= h($titel . ($untertitel !== '' ? ', ' . $untertitel : '')) ?>">
<link rel="preload" href="fonts/noto-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:"Noto Sans";font-style:normal;font-weight:100 900;font-display:swap;src:url(fonts/noto-sans-latin-wght-normal.woff2) format("woff2-variations"),url(fonts/noto-sans-latin-wght-normal.woff2) format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:"Noto Sans";font-style:normal;font-weight:100 900;font-display:swap;src:url(fonts/noto-sans-latin-ext-wght-normal.woff2) format("woff2-variations"),url(fonts/noto-sans-latin-ext-wght-normal.woff2) format("woff2");unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}
:root{--bg:#fff;--fg:#111;--mut:#6f6f6f;--line:#e6e6e6;--ph:#f1f1f1;--h:clamp(150px,21vw,260px)}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--fg);font:400 16px/1.6 "Noto Sans","Helvetica Neue",Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit}
header,main,footer{max-width:1500px;margin:0 auto;padding-left:clamp(16px,4vw,48px);padding-right:clamp(16px,4vw,48px)}
header{padding-top:clamp(32px,6vw,80px);padding-bottom:28px}
h1{font-weight:300;font-size:clamp(28px,4.2vw,48px);line-height:1.15;letter-spacing:-.01em;margin:0}
h1 a{text-decoration:none}
.sub{color:var(--mut);margin:.6em 0 0;letter-spacing:.03em}
.seiten{display:flex;flex-wrap:wrap;gap:4px 28px;margin-top:30px}
.seiten a{font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;color:var(--mut);padding:3px 0;border-bottom:1px solid transparent}
.seiten a:hover{color:var(--fg)}
.seiten a[aria-current=page]{color:var(--fg);border-bottom-color:var(--fg)}
.filter{display:flex;flex-wrap:wrap;gap:4px 20px;margin:0 0 24px}
.filter button{font:inherit;font-size:14px;background:none;border:0;border-bottom:1px solid transparent;padding:0;color:var(--mut);cursor:pointer}
.filter button:hover{color:var(--fg)}
.filter button[aria-pressed=true]{color:var(--fg);border-bottom-color:var(--fg)}
.galerie{display:flex;flex-wrap:wrap;gap:clamp(14px,2vw,28px)}
.galerie::after{content:"";flex-grow:1000000}
.item{flex-grow:calc(var(--r)*100);flex-shrink:1;flex-basis:calc(var(--r)*var(--h));color:inherit;text-decoration:none;display:block}
.item[hidden]{display:none}
.item img{display:block;width:100%;height:auto;aspect-ratio:var(--r);background:var(--ph);transition:opacity .25s}
.item:hover img,.item:focus-visible img{opacity:.88}
.item:focus-visible{outline:2px solid var(--fg);outline-offset:4px}
.item figcaption{display:flex;justify-content:space-between;gap:1em;padding-top:8px;font-size:13px;color:var(--mut)}
.item figcaption b{font-weight:400;color:var(--fg)}
.leer{color:var(--mut);max-width:34em;padding:40px 0}
main{padding-bottom:64px}
/* Textseiten */
.text{max-width:860px;margin-left:0}
.kopf,.text h2{font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--mut);margin:2.4em 0 1em;padding-bottom:.6em;border-bottom:1px solid var(--line)}
.kopf{margin:0 0 1.2em}
.text>h2:first-child,.zeit>h2:first-child{margin-top:0}
.text p{margin:0 0 .9em;max-width:44em}
.text a{text-decoration:underline;text-decoration-color:#b5b5b5;text-underline-offset:3px}
.text a:hover{text-decoration-color:var(--fg)}
.zeit{display:grid;grid-template-columns:9.5em minmax(0,1fr);column-gap:clamp(16px,3vw,40px);row-gap:.75em}
.zeit h2{grid-column:1/-1;margin:2.4em 0 .4em}
.zeit p{grid-column:1/-1;margin:0}
.jahr{color:var(--mut);font-variant-numeric:tabular-nums}
.link{display:flex;flex-wrap:wrap;align-items:baseline;gap:0 1em}
.link a{font-size:17px}
.link span{font-size:14px;color:var(--mut)}
footer{padding-top:40px;padding-bottom:48px;font-size:13px;color:var(--mut);display:flex;gap:24px;flex-wrap:wrap}
/* Großansicht */
.lb{position:fixed;inset:0;z-index:10;background:#fff;display:none;grid-template-rows:minmax(0,1fr) auto}
.lb.offen{display:grid}
.buehne{position:relative}
.rahmen{position:absolute;inset:56px clamp(56px,7vw,100px) 8px}
.rahmen img{width:100%;height:100%;object-fit:contain;opacity:0;transition:opacity .25s}
.btn{position:absolute;display:grid;place-items:center;width:56px;height:56px;border:0;background:none;color:var(--fg);cursor:pointer;opacity:.5;transition:opacity .2s;padding:0}
.btn:hover,.btn:focus-visible{opacity:1}
.btn svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.2}
.prev{left:clamp(0px,1.5vw,20px);top:50%;margin-top:-28px}
.next{right:clamp(0px,1.5vw,20px);top:50%;margin-top:-28px}
.zu{right:clamp(4px,1.5vw,20px);top:4px}
.zaehler{position:absolute;left:clamp(16px,2.5vw,32px);top:18px;font-size:13px;letter-spacing:.08em;color:var(--mut)}
.cap{text-align:center;max-width:760px;margin:0 auto;padding:10px 24px 28px}
.cap-t{font-size:20px;font-weight:400;line-height:1.3}
.cap-m{font-size:14px;color:var(--mut);margin-top:2px}
.cap-x{font-size:14px;margin-top:8px;white-space:pre-line}
.cap-m:empty,.cap-x:empty{display:none}
@media (max-width:600px){
  .prev,.next{display:none}.rahmen{inset:52px 10px 4px}
  .zeit{grid-template-columns:1fr;row-gap:0}
  .zeit .jahr{margin-top:1em}
  .zeit .jahr.gleich{display:none}
  .zeit h2{margin:2em 0 .8em}
}
</style>
</head>
<body>
<header>
  <h1><a href="?"><?= h($titel) ?></a></h1>
<?php if ($zeigeUntertitel && $untertitel !== ''): ?>
  <p class="sub"><?= h($untertitel) ?></p>
<?php endif; ?>
<?php if ($bloecke): ?>
  <nav class="seiten" aria-label="Seiten">
    <a href="?"<?= $aktiv === 'werke' ? ' aria-current="page"' : '' ?>>Werke</a>
<?php foreach (SEITEN as $key => [$label]): if (!isset($bloecke[$key])) continue; ?>
    <a href="?seite=<?= h($key) ?>"<?= $aktiv === $key ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
<?php endforeach; ?>
  </nav>
<?php endif; ?>
</header>

<main>
<?php if ($aktiv === 'werke'): ?>
<?php if (!$werke): ?>
  <p class="leer">Noch keine Bilder vorhanden. Lege Bilddateien (JPG, PNG oder WebP) in den Ordner „<?= h($ordner) ?>“ und je eine gleichnamige Textdatei dazu, siehe README.md.</p>
<?php else: ?>
<?php if ($werkeTitel !== ''): ?>
  <h2 class="kopf"><?= h($werkeTitel) ?></h2>
<?php endif; ?>
<?php if (count($rubriken) > 1): ?>
  <nav class="filter" aria-label="Rubriken">
    <button type="button" data-kat="" aria-pressed="true">Alle</button>
<?php foreach ($rubriken as $r): ?>
    <button type="button" data-kat="<?= h($r) ?>" aria-pressed="false"><?= h($r) ?></button>
<?php endforeach; ?>
  </nav>
<?php endif; ?>
  <div class="galerie">
<?php foreach ($werke as $w):
    $thumb = '?' . h(http_build_query(['i' => $w['rel'], 's' => 't', 'v' => $w['v']]));
    $gross = '?' . h(http_build_query(['i' => $w['rel'], 's' => 'l', 'v' => $w['v']]));
?>
    <a class="item" href="<?= $gross ?>" style="--r:<?= $w['ratio'] ?>"
       data-kat="<?= h($w['kat']) ?>" data-titel="<?= h($w['titel']) ?>"
       data-meta="<?= h($w['meta']) ?>" data-text="<?= h($w['text']) ?>">
      <img src="<?= $thumb ?>" alt="<?= h($w['titel']) ?>" loading="lazy" decoding="async">
      <figcaption><b><?= h($w['titel']) ?></b><span><?= h($w['jahr']) ?></span></figcaption>
    </a>
<?php endforeach; ?>
  </div>
<?php endif; ?>
<?php elseif (SEITEN[$aktiv][2] === 'zeitleiste'): ?>
  <div class="text zeit">
<?php $vorher = null; foreach ($bloecke[$aktiv] as $b): ?>
<?php if ($b['t'] === 'h'): $vorher = null; ?>
    <h2><?= h($b['text']) ?></h2>
<?php elseif ($b['t'] === 'zeile'):
    $gleich = $vorher !== null && $vorher === $b['jahr']; // gleiches Jahr wie die Zeile davor: nur einmal zeigen
    $vorher = $b['jahr']; ?>
    <div class="jahr<?= $gleich ? ' gleich' : '' ?>"><?= $gleich ? '' : h($b['jahr']) ?></div>
    <div><?= inline($b['text']) ?></div>
<?php else: $vorher = null; ?>
    <p><?= inline($b['text']) ?></p>
<?php endif; ?>
<?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="text">
<?php foreach ($bloecke[$aktiv] as $b): ?>
<?php if ($b['t'] === 'h'): ?>
    <h2><?= h($b['text']) ?></h2>
<?php elseif ($b['t'] === 'link'): ?>
    <p class="link"><a href="<?= h($b['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($b['titel']) ?></a><span><?= h($b['zusatz']) ?></span></p>
<?php else: ?>
    <p><?= inline($b['text']) ?></p>
<?php endif; ?>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</main>

<footer>
<?php if ($email !== ''): ?>
  <a href="mailto:<?= h($email) ?>"><?= h($email) ?></a>
<?php endif; ?>
<?php if ($impressum): ?>
  <a href="impressum.html">Impressum</a>
<?php endif; ?>
  <span>© <?= date('Y') ?> <?= h($copyright !== '' ? $copyright : $titel) ?></span>
</footer>

<?php if ($aktiv === 'werke' && $werke): ?>
<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Großansicht">
  <div class="buehne">
    <div class="zaehler" id="zaehler"></div>
    <button class="btn zu" id="zu" type="button" aria-label="Schließen"><svg viewBox="0 0 28 28"><path d="M6 6l16 16M22 6L6 22"/></svg></button>
    <button class="btn prev" id="prev" type="button" aria-label="Vorheriges Bild"><svg viewBox="0 0 28 28"><path d="M18 4L8 14l10 10"/></svg></button>
    <button class="btn next" id="next" type="button" aria-label="Nächstes Bild"><svg viewBox="0 0 28 28"><path d="M10 4l10 10-10 10"/></svg></button>
    <div class="rahmen"><img id="bild" alt=""></div>
  </div>
  <div class="cap">
    <div class="cap-t" id="cap-t"></div>
    <div class="cap-m" id="cap-m"></div>
    <div class="cap-x" id="cap-x"></div>
  </div>
</div>

<script>
(() => {
  const items = [...document.querySelectorAll('.item')];
  const $ = id => document.getElementById(id);
  const lb = $('lb'), bild = $('bild');
  let liste = [], i = 0, zuletzt = null;

  const sichtbar = () => items.filter(el => !el.hidden);

  function zeige() {
    const el = liste[i];
    bild.style.opacity = 0;
    bild.onload = () => { bild.style.opacity = 1; };
    bild.src = el.href;
    bild.alt = el.dataset.titel;
    $('cap-t').textContent = el.dataset.titel;
    $('cap-m').textContent = el.dataset.meta;
    $('cap-x').textContent = el.dataset.text;
    $('zaehler').textContent = (i + 1) + ' / ' + liste.length;
    // Nachbarn vorladen
    [i + 1, i - 1].forEach(k => { new Image().src = liste[(k + liste.length) % liste.length].href; });
  }
  function weiter(d) {
    if (liste.length < 2) return;
    i = (i + d + liste.length) % liste.length;
    zeige();
  }
  function oeffnen(el) {
    liste = sichtbar();
    i = liste.indexOf(el);
    zuletzt = document.activeElement;
    lb.classList.add('offen');
    document.documentElement.style.overflow = 'hidden';
    zeige();
    $('zu').focus({preventScroll: true});
  }
  function schliessen() {
    lb.classList.remove('offen');
    document.documentElement.style.overflow = '';
    bild.removeAttribute('src');
    if (zuletzt) zuletzt.focus({preventScroll: true});
  }

  items.forEach(el => el.addEventListener('click', e => {
    if (e.metaKey || e.ctrlKey || e.shiftKey) return;
    e.preventDefault();
    oeffnen(el);
  }));
  $('zu').onclick = schliessen;
  $('prev').onclick = () => weiter(-1);
  $('next').onclick = () => weiter(1);
  // Antippen / Anklicken: linke Bildhälfte = zurück, rechte Bildhälfte = weiter
  const richtung = e => { const r = bild.getBoundingClientRect(); return e.clientX - r.left < r.width / 2 ? -1 : 1; };
  bild.addEventListener('click', e => weiter(richtung(e)));
  bild.addEventListener('mousemove', e => { bild.style.cursor = richtung(e) < 0 ? 'w-resize' : 'e-resize'; });
  lb.addEventListener('click', e => { if (e.target.closest('.cap, .buehne') === e.target) schliessen(); });

  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('offen')) return;
    if (e.key === 'ArrowRight') { e.preventDefault(); weiter(1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); weiter(-1); }
    else if (e.key === 'Escape') schliessen();
  });

  // Wischen auf dem Handy
  let sx = 0, sy = 0;
  lb.addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, {passive: true});
  lb.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) weiter(dx < 0 ? 1 : -1);
  }, {passive: true});

  // Rubriken filtern
  document.querySelectorAll('.filter button').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.filter button').forEach(x => x.setAttribute('aria-pressed', x === b));
    const k = b.dataset.kat;
    items.forEach(el => { el.hidden = k !== '' && el.dataset.kat !== k; });
  }));
})();
</script>
<?php endif; ?>
</body>
</html>
