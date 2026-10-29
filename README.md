# Portfolio Hans Georg Dornhege

Eine schlichte Website für Bilder, Vita, Ausstellungen und Links. Es gibt kein Login und keine Datenbank. Alles, was sich ändern kann, steht in einfachen Textdateien und in einem Bilderordner. Wer eine Datei ändern und hochladen kann, kann die Seite pflegen.

Die Seite zeigt alle Bilder als Vorschau. Ein Klick vergrößert ein Bild, mit den Pfeiltasten (am Handy durch Wischen oder Antippen der linken oder rechten Bildhälfte) geht es zum vorherigen oder nächsten Bild.

## Was liegt wo

```
index.php              die Seite selbst, oben stehen ein paar Einstellungen
werke/                 Bilder und je eine Textdatei dazu
  01_Mama/             optional: Unterordner werden zu Rubriken
    01_Abendlicht.jpg
    01_Abendlicht.txt
texte/
  vita.txt             Vita
  ausstellungen.txt    Ausstellungen
  links.txt            Links, Artikel, Literatur
fonts/                 die Schrift Noto Sans (liegt auf dem eigenen Server)
impressum.html         optional, wird in der Fußzeile verlinkt
cache/                 entsteht von selbst, nicht anfassen
```

Gibt es eine Datei in `texte` nicht oder ist sie leer, verschwindet der Menüpunkt dazu. Eine ganze Seite blendet man also aus, indem man die Datei leert oder löscht.

## Ein neues Werk hinzufügen

1. Das Bild in den Ordner `werke` legen (oder in einen Unterordner davon). Erlaubt sind JPG, PNG, WebP und GIF. Große Fotos direkt vom Handy oder aus der Kamera sind in Ordnung, die Seite verkleinert sie selbst.
2. Daneben eine Textdatei mit **genau demselben Namen** anlegen, nur mit der Endung `.txt`. Zu `abendlicht.jpg` gehört `abendlicht.txt`.
3. In die Textdatei schreiben, eine Angabe pro Zeile:

```
Titel: Abendlicht
Jahr: 2019
Maße: 80 x 100 cm
Technik: Öl auf Leinwand
Beschreibung: Ein optionaler Text, auch über mehrere Zeilen.
Hier geht der Text in der zweiten Zeile weiter.
```

Alles davon ist optional. Fehlt die Textdatei ganz, steht der Dateiname als Titel unter dem Bild. Statt "Maße" funktioniert auch "Größe" oder "Format", statt "Technik" auch "Material". Eigene weitere Angaben wie `Serie: Licht` erscheinen unter der Beschreibung.

**Reihenfolge:** Die Bilder erscheinen in der Reihenfolge der Dateinamen. Wer sie selbst ordnen möchte, setzt Zahlen davor (`01_`, `02_`, ...). Die Zahlen werden nicht angezeigt.

**Rubriken:** Unterordner in `werke` werden zu Rubriken, zum Beispiel ein Ordner pro Künstlerin oder Künstler, oder pro Werkgruppe. Gibt es mindestens zwei, erscheint über den Bildern eine Auswahl. Der Ordnername ist der Name der Rubrik, vorangestellte Zahlen und Unterstriche werden dabei nicht angezeigt.

**Ein Werk entfernen:** Bild und Textdatei löschen. **Vorübergehend ausblenden:** einen Punkt an den Anfang des Dateinamens setzen (`.abendlicht.jpg`). Dateien und Ordner, die mit einem Punkt beginnen, ignoriert die Seite.

## Vita, Ausstellungen und Links ändern

Diese Texte stehen in den drei Dateien im Ordner `texte`. Oben in jeder Datei erklären Kommentarzeilen noch einmal die Schreibweise. Es genügt ein normaler Texteditor (Editor unter Windows, TextEdit im Textmodus am Mac). Bitte als UTF-8 speichern, damit Umlaute richtig ankommen.

### Vita und Ausstellungen

Eine Zeile pro Eintrag, links das Jahr, dann ein Doppelpunkt und ein Leerzeichen:

```
# Einzelausstellungen (Auswahl)
1999: „Kardinal am Meer“, Kunstverein Kaponier, Vechta
2008: „Buen Camino“, Galerie Michael Nolte, Münster

# Gruppenausstellungen (Auswahl)
1980 bis 2009: Große Düsseldorfer Kunstausstellung, Düsseldorf
```

- Eine Zeile mit `#` am Anfang ist eine Zwischenüberschrift.
- Eine Zeile ohne Jahr ist ein normaler Absatz, zum Beispiel ein einleitender Satz.
- Als "Jahr" gilt alles vor dem ersten Doppelpunkt, wenn es eine Ziffer enthält und nicht länger als etwa 25 Zeichen ist. Es kann also auch `1981/82` oder `seit 2008` dort stehen.
- Die Reihenfolge der Zeilen bleibt, wie sie in der Datei steht. Wer die neueste Ausstellung oben haben möchte, schreibt sie an den Anfang.
- Steht dasselbe Jahr in mehreren Zeilen direkt untereinander, wird es nur einmal angezeigt.
- Links im Text schreibt man so: `[Bericht in der Zeitung](https://adresse.de)`.
- Zeilen, die mit `//` beginnen, sind Notizen und werden nicht angezeigt.

### Links

Eine Zeile pro Link, die drei Teile mit einem senkrechten Strich getrennt:

```
# Artikel
Porträt in der Zeitung | https://www.beispiel.de/artikel | Tagesspiegel, 12.3.2024

# Literatur
Eckhart J. Gillen (Hrsg.): Großgörschen 35, Berlin 2014.
```

Der dritte Teil (Zusatz) ist optional. Ohne Zusatz erscheint die Webadresse. Eine Zeile ohne Adresse wird als normaler Text angezeigt, so lassen sich auch Bücher und Kataloge aufführen. Mit `//` am Zeilenanfang blendet man einen Link vorübergehend aus.

## Einstellungen

Ganz oben in `index.php` stehen die Einstellungen, jeweils mit einer Erklärung dahinter:

```php
$titel           = 'Hans Georg Dornhege';
$untertitel      = 'Malerei · Zeichnung · Skulptur';
$zeigeUntertitel = true;               // false = Untertitel ausblenden
$werkeTitel      = 'Werke (Auswahl)';  // Überschrift über den Bildern, leer = keine
$copyright       = 'Pablo Dornhege';   // Name in der Fußzeile
$email           = '';                 // z. B. 'pablo@dornhege.com', leer = kein Kontaktlink
```

Beim Bearbeiten darauf achten, dass die Anführungszeichen und das Semikolon am Zeilenende stehen bleiben.

## Auf den Webspace bringen

Der Webspace braucht PHP 8.0 oder neuer mit der GD-Erweiterung. Das bieten fast alle Hoster an, im Zweifel beim Hoster nachsehen.

1. Den ganzen Ordner (ohne `cache`, falls vorhanden) auf den Webspace hochladen, per FTP-Programm (zum Beispiel FileZilla) oder über den Dateimanager des Hosters. Wenn möglich SFTP oder FTPS verwenden, weil klassisches FTP das Passwort unverschlüsselt überträgt.
2. Danach die Adresse im Browser öffnen. Die Seite sollte sofort da sein.
3. Später genügt es, nur die geänderten Dateien hochzuladen: neue Bilder samt Textdatei in `werke`, geänderte Texte in `texte`.

Beim ersten Aufruf legt die Seite den Ordner `cache` an und erzeugt darin verkleinerte Bilder. Dafür braucht sie Schreibrechte im Hauptordner. Der erste Aufruf mit vielen neuen Bildern dauert deshalb etwas länger, danach geht es schnell. Man kann `cache` jederzeit löschen, er baut sich neu auf.

**Die Originale sind geschützt:** Die Seite liefert nur verkleinerte Fassungen aus. Auf Apache-Servern sorgt die Datei `werke/.htaccess` zusätzlich dafür, dass die Originalbilder nicht direkt über ihre Adresse abrufbar sind.

**Impressum:** Wird die Seite öffentlich betrieben und werden Werke verkauft, ist in Deutschland in der Regel ein Impressum nötig. Eine Datei `impressum.html` neben `index.php` wird automatisch in der Fußzeile verlinkt. Eine rechtliche Beratung ersetzt das nicht.

## Wenn etwas nicht klappt

| Problem | Meist hilft das |
|---|---|
| Ein Bild erscheint nicht | Endung prüfen (JPG, PNG, WebP, GIF), Dateiname darf nicht mit einem Punkt beginnen, Datei muss im Ordner `werke` oder einem direkten Unterordner liegen (tiefer verschachtelte Ordner werden nicht gelesen). |
| Titel oder Maße fehlen unter dem Bild | Die Textdatei muss genau so heißen wie das Bild, nur mit `.txt`. Pro Zeile `Name: Wert`, mit Doppelpunkt. |
| Umlaute sind kaputt | Die Textdatei als UTF-8 speichern. Dateien aus dem Windows-Editor (ANSI) werden ebenfalls erkannt. |
| Eine Zeile in Vita oder Ausstellungen steht nicht in der Jahresspalte | Nach dem Doppelpunkt muss ein Leerzeichen folgen, und vor dem Doppelpunkt muss eine Ziffer stehen. |
| Ein Menüpunkt fehlt | Die Datei in `texte` fehlt, ist leer oder enthält nur Notizzeilen mit `//`. |
| Neues Bild ist nach dem Hochladen noch nicht zu sehen | Die Seite im Browser neu laden (Strg+F5 oder Cmd+Shift+R). |
| Die Vorschaubilder laden sehr langsam | Vermutlich fehlen Schreibrechte für den Ordner `cache`, oder GD ist nicht aktiviert. Dann liefert die Seite die großen Originale aus. Beim Hoster nachfragen. |
| Ein Bild bleibt grau | Die Datei ist beschädigt oder für den Arbeitsspeicher des Servers zu groß. Das Bild verkleinern (zum Beispiel auf 6000 Pixel Kantenlänge) und neu hochladen. |
| Die ganze Seite ist weiß oder zeigt eine Fehlermeldung | Fast immer eine zu alte PHP-Version. PHP 8.0 oder neuer einstellen, das geht meist im Kundenbereich des Hosters. |

## Lokal ausprobieren

Auf einem Computer mit installiertem PHP: im Ordner `php -S localhost:8000` eingeben und `http://localhost:8000` im Browser öffnen.

## Schrift und Datenschutz

Die Schrift Noto Sans (Lizenz: SIL Open Font License, Text im Ordner `fonts`) liegt auf dem eigenen Server. Die Seite lädt nichts von Google oder anderen Diensten nach, setzt keine Cookies und benutzt keine Statistik.

## Weiterentwicklung

Eine ausführliche technische Beschreibung für Entwicklerinnen, Entwickler und KI-Assistenten steht in `CLAUDE.md`.
