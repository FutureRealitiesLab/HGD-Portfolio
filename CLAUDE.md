# CLAUDE.md: Portfolio-Website Hans Georg Dornhege

Diese Datei beschreibt das Projekt für KI-Assistenten (Claude Code) und Entwickler. Für den Alltag der Redakteure gibt es `README.md`.

## Zweck und Rahmen

- Portfolio-Website für den Künstler Hans Georg Dornhege, aufgesetzt von seinem Sohn Pablo Dornhege (Auftraggeber, Copyright-Name in der Fußzeile). Beide Eltern sind Künstler, die Seite ist aktuell nur für Hans Georg Dornhege konfiguriert.
- Leitidee: **kein CMS**. Redakteure legen Bilder samt gleichnamiger `.txt` in einen Ordner und pflegen Vita, Ausstellungen und Links in Textdateien. Die Seite liest alles bei jedem Aufruf selbst ein.
- Muss einfach und elegant bleiben: weißer Hintergrund, Noto Sans, viel Weißraum, Bilder im Mittelpunkt.
- Sprache der Oberfläche, der Inhalte und der Bezeichner im Code: **Deutsch**.

## Harte Randbedingungen

- Ein einziges Skript `index.php`. Keine Datenbank, kein Build-Schritt, kein Framework, keine Composer- oder npm-Abhängigkeiten.
- PHP 8.0 oder neuer (`match`, `str_contains`, `str_starts_with`). GD und mbstring werden benutzt, exif optional.
- Keine externen Anfragen im Browser: keine CDNs, keine Google Fonts, kein Tracking, keine Cookies. Die Schrift liegt lokal in `fonts/` (Datenschutz nach DSGVO).
- Redakteure sind keine Entwickler. Alles, was sie ändern, muss mit einem Texteditor und Hochladen gehen. Kommentare in den Inhaltsdateien sind Dokumentation für sie und gehören dort hin.
- Texte, die für Pablo geschrieben werden (Chat, Doku, Inhaltsdateien), enthalten **keine Gedankenstriche** (weder Geviert- noch Halbgeviertstrich). Stattdessen Komma, Doppelpunkt, "bis" oder einen Bindestrich in Wörtern.

## Dateistruktur

```
index.php               gesamte Anwendung (Einstellungen, Funktionen, Bildauslieferung, HTML, CSS, JS)
README.md               Anleitung für Menschen
CLAUDE.md               diese Datei
fonts/                  Noto Sans Variable (woff2: latin, latin-ext) und OFL-Lizenztext
werke/                  Bilder und .txt dazu, Unterordner = Rubriken (eine Ebene tief)
werke/.htaccess         "Require all denied" (Apache 2.4): Originale nicht direkt abrufbar
texte/vita.txt          Vita
texte/ausstellungen.txt Ausstellungen
texte/links.txt         Links, Artikel, Literatur
impressum.html          optional, nicht vorhanden; wird in der Fußzeile verlinkt, wenn es existiert
cache/                  wird zur Laufzeit angelegt (verkleinerte JPEGs), gehört nicht ins Repository und wird nicht hochgeladen
```

Der Ordner `werke` ist im Auslieferungsstand leer (nur `.htaccess`). Die echten Bilder liefern die Eltern.

## Aufbau von index.php (in dieser Reihenfolge)

1. **Einstellungen** (oben, für Redakteure gedacht): `$titel`, `$untertitel`, `$zeigeUntertitel`, `$werkeTitel` (Überschrift über den Bildern, leer = keine), `$copyright` (leer = `$titel`), `$email` (leer = kein Link), `$ordner` (`werke`), `$texte` (`texte`).
2. **Konstanten:** `BILD_ENDUNGEN` (jpg, jpeg, png, webp, gif), `GROESSEN` (`t` = 700, `l` = 2200 Pixel längste Seite), `SEITEN` (Adresse => [Menüname, Datei, Art], Art ist `zeitleiste` oder `links`), `FELD_ALIAS` (Schreibvarianten der Werkangaben).
3. **Hilfsfunktionen:** `h()` (HTML-Escape), `inline()` (Escape plus `[Text](http(s)://...)` als Link mit `target=_blank rel="noopener noreferrer"`), `ist_bild()`, `schoen()` (entfernt führende Zahl und ersetzt `_` durch Leerzeichen), `lade_text()` (BOM entfernen, Windows-1252 nach UTF-8 falls nötig), `lese_zeilen()`, `parse_zeitleiste()`, `parse_links()`, `exif_ausrichtung()`, `verkleinert()`, `lese_info()`, `sammle()`.
4. **Bildauslieferung** (vor jeglicher HTML-Ausgabe): Aufruf `?i=<relativer Pfad in werke>&s=t|l&v=<mtime>`.
5. **Seiten einlesen:** alle `SEITEN` parsen, `$bloecke[$key]` nur wenn Inhalt vorhanden. `$aktiv` kommt aus `?seite=`, unbekannt oder leer fällt auf `werke` zurück.
6. **Werke einlesen** nur wenn `$aktiv === 'werke'`.
7. **HTML** mit eingebettetem CSS, Menü, Inhalt je nach `$aktiv`, Fußzeile, Lightbox-Markup und Script (nur auf der Werke-Seite und nur wenn Werke vorhanden sind).

## Routing

- `?` oder leer: Werke (Startseite). `?seite=vita|ausstellungen|links`: Textseiten.
- Ein Menüpunkt (und die Seite) existiert nur, wenn die Datei in `texte/` nach dem Parsen mindestens einen Block liefert. Gibt es keine einzige Textseite, wird das Menü nicht gerendert.
- `<title>`: Startseite `$titel`, Unterseiten `Seitenname · $titel`.

## Dateiformate (maßgeblich: die Parser im Code)

### Werk-Textdatei (`werke/**/name.txt`, gleicher Basisname wie das Bild)

- Suche nach `.txt`, `.TXT`, `.Txt`. Zeilenformat `Schlüssel: Wert` (Regex `^\s*(\p{L}[\p{L}\p{N} _-]{0,24})\s*:\s*(.*)$`), Schlüssel unabhängig von Groß-/Kleinschreibung.
- Normalisierung über `FELD_ALIAS`: `titel` (title, name), `jahr` (year, datum, entstehungsjahr), `masse` (maße, größe, format, abmessungen, maß), `technik` (material, medium), `text` (beschreibung, notiz, anmerkung).
- Zeilen ohne Schlüssel hängen als neue Zeile an das vorherige Feld (oder bilden `text`, wenn noch keines existiert).
- Anzeige: Titel (Fallback `schoen(Dateiname)`), Meta-Zeile `jahr · masse · technik` (nur vorhandene), darunter `text` und alle unbekannten Schlüssel als `Schlüssel: Wert`.

### Bilder und Ordner

- Sortierung `natcasesort` nach Dateiname. Erst Bilder direkt in `werke/`, dann je Unterordner (ebenfalls `natcasesort`). Nur **eine** Unterordner-Ebene.
- Namen mit führendem Punkt werden ignoriert (Datei und Ordner). Das ist der dokumentierte Weg, ein Werk auszublenden.
- Unterordnername (durch `schoen()` bereinigt) = Rubrik. Die Rubrikauswahl erscheint nur bei **mindestens zwei** Rubriken. Bilder im Wurzelordner haben keine Rubrik und sind nur unter "Alle" sichtbar.
- Seitenverhältnis aus `getimagesize`, bei EXIF-Orientierung 5 bis 8 vertauscht (für das Layout).

### Vita und Ausstellungen (`parse_zeitleiste`)

Zeilenweise, nach `lese_zeilen()` (Leerzeilen und Zeilen mit `//` am Anfang entfallen):

- `# Text` (ein oder mehrere `#`): Zwischenüberschrift (`h2`).
- `Label: Text` mit Regex `^([^:]{1,25}?)\s*:\s+(.+)$`, **und** das Label muss eine Ziffer enthalten: Zeile mit Jahresspalte (`1981/82`, `1964 bis 1970`, `seit 2008`). Nach dem Doppelpunkt ist ein Leerzeichen Pflicht, damit URLs (`https://...`) nie als Label gelten.
- Alles andere: Absatz über die volle Breite.
- Reihenfolge der Datei bleibt erhalten, es wird nicht sortiert.
- Rendering: Hat eine Zeile dasselbe Jahr wie die direkt vorherige Zeile (ohne Überschrift oder Absatz dazwischen), wird die Jahrescelle leer gerendert (`.jahr.gleich`). Auf dem Desktop bleibt die leere Zelle erhalten (Grid-Spalte), auf dem Handy wird sie ausgeblendet.
- Inline-Links im Text: `[Text](https://adresse)`. Nur http(s).

### Links (`parse_links`)

- `# Text`: Überschrift.
- `Titel | Adresse | Zusatz` (Zusatz optional). Fehlt der Titel und steht nur eine Adresse da (`https://...` oder `www....`), wird der Host als Titel genutzt. `www.` ohne Schema bekommt `https://`. Ohne Zusatz erscheint der Host als Zusatz.
- Zeile ohne gültige http(s)-Adresse: normaler Absatz (zum Beispiel Literaturangaben). Das ist gewollt und kein Fehler.
- Ausgabe öffnet in neuem Tab mit `rel="noopener noreferrer"`.

## Bildverarbeitung und Caching

- `verkleinert($pfad, $max)`: erzeugt `cache/<md5(pfad|mtime|max)>.jpg` (JPEG, Qualität 86, progressiv), längste Seite höchstens `$max`, nie hochskaliert. Transparenz wird auf Weiß gesetzt. EXIF-Rotation für 3, 6, 8 (gespiegelte Varianten 2, 4, 5, 7 werden nicht korrigiert). Schreiben über temporäre Datei und `rename`.
- Fällt auf das Original zurück, wenn GD fehlt, `cache/` nicht angelegt werden kann oder das Bild nicht dekodiert wird.
- `memory_limit` wird zur Laufzeit auf 1024M angehoben (`@ini_set`).
- Header: `Cache-Control: public, max-age=2592000`. Die URL enthält `v=<mtime>`, dadurch wird bei ausgetauschtem Bild neu geladen.
- Vorschau `s=t` (700 px) im Raster, Großansicht `s=l` (2200 px) in der Lightbox. Das Original wird nie ausgeliefert, solange GD arbeitet.

## Sicherheit

- Pfadzugriff: `realpath` der Anfrage muss mit `realpath(werke) . DIRECTORY_SEPARATOR` beginnen, muss eine Datei sein und eine erlaubte Bildendung haben, sonst 404. Null-Bytes und Array-Parameter werden abgefangen. Getestet: `?i=../index.php`, `.txt`-Zugriff und `i[]=` liefern 404.
- Jede Ausgabe läuft über `h()` oder `inline()`. Links werden nur für `http://` und `https://` erzeugt (kein `javascript:`).
- `werke/.htaccess` sperrt den direkten Zugriff auf Originale (nur Apache 2.4). Unter nginx muss das in der Serverkonfiguration nachgebildet werden.
- Es gibt kein Login und keine Schreibzugriffe über das Web, außer dem Anlegen von `cache/`.

## Frontend

- **Design-Tokens** (`:root`): `--bg #fff`, `--fg #111`, `--mut #6f6f6f`, `--line #e6e6e6`, `--ph #f1f1f1`, `--h clamp(150px,21vw,260px)`. Schrift `"Noto Sans"` (variabel, Gewichte 100 bis 900 aus zwei woff2-Dateien mit `unicode-range`, `font-display: swap`, Preload der latin-Datei). Titel Gewicht 300, Menü und Überschriften 13 px, Gewicht 500, Großbuchstaben, Laufweite .1em.
- **Raster:** Flex-Wrap-"Justified"-Layout in reinem CSS. Pro Bild `--r` = Seitenverhältnis, `flex-grow: r*100`, `flex-basis: r*var(--h)`, `.galerie::after` mit sehr hohem `flex-grow` verhindert gestreckte letzte Zeile. Bild `aspect-ratio: var(--r)` gegen Layoutsprünge, `loading="lazy"`.
- **Lightbox:** `#lb` Vollbild in Weiß. Bedienung: Pfeiltasten links/rechts, Esc, Schließen-Button, Klick oder Antippen der **linken Bildhälfte = zurück, rechten Bildhälfte = weiter** (Mauszeiger `w-resize`/`e-resize`), Wischen ab 50 px horizontal, Pfeil-Buttons nur ab 600 px Breite, Endlos-Umlauf, Nachbarbilder werden vorgeladen, Zähler `3 / 24`, Fokus wird beim Schließen zurückgesetzt, `html` bekommt `overflow:hidden`. Navigiert wird nur durch die **aktuell sichtbaren** (gefilterten) Bilder. Das Bild liegt absolut im `.rahmen` mit `object-fit: contain`.
- **Rubrikfilter:** reines Client-Script, setzt `hidden` an `.item`. Ohne JavaScript sind alle Bilder sichtbar, und ein Klick öffnet die große Bilddatei direkt (Fallback über das `href` der Anker).
- **Textseiten:** `.text` mit max. 860 px, `.zeit` als Grid (`9.5em` Jahresspalte, darunter Text), auf dem Handy einspaltig mit dem Jahr über dem Text.
- Fußzeile: optionale E-Mail, optional Impressum-Link, `© <Jahr> <$copyright>`.

## Entwicklung und Tests

- Syntax: `php -l index.php`. Lokal: im Projektordner `php -S 127.0.0.1:8000`.
- Zum Testen Beispielbilder in `werke/` (zwei Unterordner, ein PNG, ein Panorama, ein Hochformat) und Textdateien anlegen. Diese Testdaten nie ins Repository oder in die Auslieferung übernehmen.
- Prüfen im Headless-Browser (Playwright Chromium), jeweils Desktop (1400 px) und Handy (390 px, mit Touch): Startseite, drei Textseiten, Lightbox (Öffnen, Pfeiltasten, Tippen links/rechts, Esc, Umlauf), Rubrikfilter, `?seite=gibtsnicht`, keine Konsolenfehler, keine 4xx-Antworten, kein horizontales Scrollen, PHP-Log ohne Warnungen.
- Nach CSS-Änderungen Screenshots ansehen, nicht nur Zahlen prüfen.

## Stolpersteine, die schon aufgetreten sind

- **Klassennamen-Kollision:** `.leer` ist der Leerzustand der Galerie (mit 40 px Padding). Eine zweite Verwendung für leere Jahreszellen blähte Zeilen auf. Neue Klassen vorher mit `grep` auf Kollisionen prüfen. Die Jahres-Klasse heißt deshalb `.jahr.gleich`.
- **`hidden` an Flex-Items:** `display:flex` überschreibt das Attribut. Deshalb gibt es `.item[hidden]{display:none}`.
- **Spezifität:** `.text > h2:first-child` und `.zeit > h2:first-child` müssen die allgemeinen `h2`-Abstände schlagen, sonst entsteht oben Leerraum.
- **PHP-Alternativsyntax:** `foreach` und `if` über mehrere `<?php ... ?>`-Blöcke sind empfindlich. Nach Änderungen immer `php -l` und Ansicht prüfen.
- Lightbox: eine halbtransparente Hintergrundfarbe ließ die Seite durchscheinen. Der Hintergrund ist deckend (`#fff`).

## Deployment (geplant, noch nicht eingerichtet)

- Hoster und Zugangsdaten sind unbekannt. Hochladen soll per SFTP oder FTPS erfolgen. Klassisches FTP nur, wenn es nicht anders geht.
- Zugangsdaten niemals in den Chat, ins Repository oder in Skripte schreiben. Besser SSH-Schlüssel oder `~/.netrc` (`chmod 600`).
- Vorschlag `deploy.sh` (ungetestet, Host, Benutzer und Zielordner anpassen). Lädt nur Neues oder Geändertes und löscht auf dem Server nichts:

```bash
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

HOST="sftp://dein-server.example"   # oder ftps://dein-server.example
USER="benutzername"
REMOTE_DIR="/htdocs"                # Zielordner auf dem Server

lftp -u "$USER" "$HOST" -e "set ssl:verify-certificate yes; \
mirror -R --only-newer --verbose \
  --exclude-glob cache/ --exclude-glob .DS_Store \
  --exclude-glob README.md --exclude-glob CLAUDE.md --exclude-glob deploy.sh \
  . $REMOTE_DIR; bye"
```

- `cache/` nie hochladen. Nach dem Deploy die Seite im Browser öffnen und prüfen (Startseite, drei Textseiten, ein Bild vergrößern).
- Claude Code fragt vor jedem Shell-Befehl um Erlaubnis. Das Hochladen darf nur auf ausdrücklichen Wunsch von Pablo geschehen.

## Inhaltlicher Stand und offene Punkte

Inhalte sind Entwürfe und müssen von den Eltern geprüft werden. Nichts davon darf als gesichert gelten.

- `texte/ausstellungen.txt`: Einzelausstellungen von 1964 bis 2012 und 2021 stammen aus einer von Pablo gelieferten Liste (maßgeblich). 2022 (Emsdetten, "Zeitspuren") stammt aus einer Online-Quelle. Die Gruppenausstellungen kommen aus dem Wikipedia-Artikel und der HDZeit (2024, Bad Oeynhausen). Die Liste ist erkennbar unvollständig, Online-Quellen für 2013 bis heute sind dünn. Überschriften tragen bewusst den Zusatz "(Auswahl)".
- `texte/vita.txt`: Entwurf nach Wikipedia, einem alten Profil des Künstlersonderbundes (Stand 2007: Studium 1959 bis 1965, Lehre Uni Dortmund 1973 bis 2007) und der Seite der Heimatfreunde Angelmodde (Vorstandsbeisitzer, gewählt 2018, aktueller Stand ungeprüft). Kuratierung der Ausstellung "In Verbindung" (Mai 2025, Gallitzinhaus).
- `texte/links.txt`: aktiv sind Emsdettener Kunstverein, HDZeit-Artikel, Heimatfreunde Angelmodde (Startseite und Ausstellung "In Verbindung"), Wikipedia, Künstlersonderbund-Profil. Auskommentiert und ungeprüft: Galerie Simon Nolte, Galerie Nolte, ArtFacts (teils alte http-Adressen).
- `$email` ist leer. Beispiel im Kommentar: `pablo@dornhege.com`.
- Kein `impressum.html` vorhanden. Bei öffentlichem Betrieb mit Verkauf von Werken in Deutschland in der Regel erforderlich, ebenso eine Datenschutzerklärung, falls Kontaktformulare oder Dienste dazukommen (derzeit keine).
- Offen: Wie die zweite Künstlerin bzw. der zweite Künstler eingebunden wird (Rubriken über Unterordner in `werke` sind vorbereitet, alternativ eigene Seite oder eigene Vita).
- Mögliche nächste Schritte, nur auf Anfrage: `deploy.sh` einrichten, Porträtfoto auf der Vita-Seite, Sortierung "neueste zuerst" als Option, Datenschutz-/Impressumsseiten, `sitemap.xml` und Vorschaubild für das Teilen (Open Graph).

## Arbeitsweise mit Pablo

- Kurz und direkt antworten, den fachlichen Hintergrund in einfacher Sprache dazu erklären.
- Fertige, sofort benutzbare Ergebnisse liefern (vollständige Dateien statt Schnipsel), vor der Übergabe testen und ehrlich sagen, was nicht getestet werden konnte.
- Bestehende Inhalte in `werke/` und `texte/` nicht ungefragt ändern. Bei Änderungen an `index.php` die Einstellungen am Dateianfang und das Verhalten für Redakteure stabil halten.
- Bei Änderungen am Dateiformat der Textdateien immer `README.md` und die Kommentarköpfe in `texte/*.txt` mit anpassen.
