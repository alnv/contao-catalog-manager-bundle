
**Wichtig: Diese Erweiterung nicht mit Catalog-Manager v1 installieren!**

## Catalog Manager (v3) - Die Enterprise-Erweiterung für Contao CMS

Der Catalog Manager hat sich bereits bei über 100 Contao-Projekten bewährt. 

Überzeuge Dich selbst und installiere eine kostenfreie und uneingeschränkte Testversion. Kaufe erst eine Lizenz, wenn Dein Projekt fertiggestellt ist. Bis dahin stehen wir Dir mit Unterstützung und Beratung zur Seite.

### Anwendungsbereiche des Catalog Managers:

- **Umkreissuchen**: Finde Ergebnisse in Deiner Nähe schnell und effizient.
- **Jobbörsen**: Verwalte und präsentiere Stellenangebote und Bewerbungen.
- **Branchenbücher**: Erstelle umfassende Verzeichnisse und Kataloge für verschiedene Branchen.
- **Mitgliederverwaltung**: Organisiere Deine Mitglieder und deren Informationen unkompliziert.
- **Immobilienbörsen**: Verwalte und präsentiere Immobilienangebote übersichtlich.
- **Suchen & Filtern**: Nutze leistungsstarke Such- und Filterfunktionen für Deine Daten.
- **Produktverwaltung**: Organisiere und präsentiere Deine Produkte optimal.
- **Merklisten/Wunschlisten**: Biete Nutzern die Möglichkeit, Favoriten zu speichern und wiederzufinden.
- **ehrsprachigkeit**: Unterstütze mehrere Sprachen und erreiche ein internationales Publikum.

Teste den Catalog Manager und lass Dich von seinen umfangreichen Funktionen überzeugen. Unser Team unterstützt Dich gerne bei der Umsetzung Deiner Projekte.

## How to

### Katalog anlegen
Deinen Katalog kannst Du ganz einfach unter "Catalog Manager" anlegen. Grundsätzlich ist alles selbsterklärend.

### Navigations-Einstellungen

Bei den Navigationseinstellungen kannst Du nur bestehende Navigationspunkte auswählen. Wenn Du einen eigenen Navigationspunkt hinzufügen möchtest, musst Du diesen in Deiner **"contao/config/config.php"** deklarieren. Hier ist ein Beispiel, wie das geht:

``` php
use Contao\ArrayUtil;

ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 1, [
'my_catalogs' => []
]);
```

So kannst Du eigene Navigationspunkte erstellen und Deinen Katalog individuell anpassen.

#### Kind Katalog hinzufügen

Wenn Du eine Eltern-Kind-Beziehung zwischen Deinen Katalogen herstellen möchtest, musst Du Deine Kind-Kataloge einfach als Unterpunkte zum Eltern-Katalog anlegen. Das funktioniert genauso wie beim Anlegen von Unterseiten in der Seitenstruktur.

Wichtig: Sobald Dein Katalog "Datensätze" enthält, kannst Du die Kataloge nicht mehr verschieben!

### Fieldsets und Paletten

Es ist auch möglich, Deinen Katalog nach Fieldsets, Subpaletten oder Typen zu unterteilen. Dies kannst Du unter "Paletten bearbeiten" (grünes Icon, 3. von links) machen. Hier sind ein paar Beispielkonfigurationen:

![](https://catalog-manager.org/files/docs/screenshot-pal-1.png)
![](https://catalog-manager.org/files/docs/screenshot-pal-2.png)

Mit diesen Einstellungen kannst Du Deinen Katalog nach Deinen Bedürfnissen strukturieren und anpassen.

### Rollen


In der neuen Version des Catalog Managers kannst Du keine Backend-CSS-Klassen, SQL-Datentypen oder reguläre Ausdrücke direkt definieren. Stattdessen läuft alles über Rollen, daher ist es wichtig, für jeden Feldtyp die passende Rolle auszuwählen. Hier ist eine Übersicht der wichtigen Rollen:

- title: Sollte jeder Katalog haben; wird für den Seitentitel verwendet.
- alias: Wenn Du ein Eingabefeld für den Alias haben willst (wichtig: Feldname muss "alias" lauten).
- miscellaneous: Binärer Feld-Typ (blob NULL); kann für Auswahllisten oder allgemeine Widgets verwendet werden.
- image: Für Bilder.
- gallery: Für Bildergalerien.
- file: Für einzelne Dateien.
- files: Für mehrere Dateien.
- textfield: varchar(255) … für alle Felder, die keiner speziellen Rolle zugeordnet werden können.

Ab Version 3.2 wird es möglich sein, eigene Rollen zu definieren.

### Listen 

Auch Listen sind selbsterklärend. Du kannst für alle Deine Kataloge (generell für alle Tabellen) eine Liste ausgeben. Einzig die Ausgabe der Daten im Frontend ist etwas "schwierig". Wie beim Vorgänger gibt es kein Backend-Modul für die Frontend-Ausgabe, das heißt, Du musst auch hier ein Template anlegen.

Im Gegensatz zum Catalog Manager v1 gibt es hier zwei Listen: einmal als Inhaltselement und einmal als Frontend-Modul. Im Inhaltselement gibt es ein Template ce_listview_*, in dem alle Einträge in ein Array übergeben werden. Dieses Array kannst Du in einer foreach-Schleife auslesen. Auf die Felder kannst Du über den Feldnamen zugreifen.

``` php
<div class="cm-refrence-list block">
    <?php foreach ($this->entities as $arrEntity): ?>
        <div class="cm-refrence-entity">
            <div class="entity-image">
                <a href="<?= $arrEntity['masterUrl'] ?>">
                    <?php if (!empty($arrEntity['image'])): ?>
                        {{image::<?= $arrEntity['image'][0]['path'] ?>?mode=crop&width=400&height=400}}
                    <?php endif; ?>
                </a>
            </div>
            <div class="entity-content">
                <h3 class="entity-headline"><?= $arrEntity['title'] ?></h3>
                <?php if ($arrEntity['teaser']): ?>
                    <div class="entity-teaser">
                        <p><?= $arrEntity['teaser'] ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="entity-link"><a href="<?= $arrEntity['masterUrl'] ?>">Weiterlesen</a></div>
        </div>
    <?php endforeach;?>
</div>
<?= $this->pagination ?>
```

Mit diesem Template kannst Du Deine Katalogeinträge im Frontend anzeigen lassen. Die Einträge werden in einer foreach-Schleife durchlaufen und entsprechend formatiert ausgegeben. Das Frontend-Modul besteht aus einem "wrapper"-Template mod_listing_table und die jeweiligen Einträge werden in einem eigenen Template cm_listing_* ausgegeben. Hier ein Beispiel:

``` php
<?php
use Contao\Date;
$arrEvent = $this->getParent();
$strUrl = array_values($arrEvent['pages'])[0]['master'] ?? '';
?>

<div class="cm-event-entity">
    <?php if ($this->origin['startDate']): ?>
    <div class="entity-date">
        <div class="date-day"><p><?= Date::parse('d', $this->origin['startDate']) ?>.</p></div>
        <div class="date-month"><p><?= Date::parse('M', $this->origin['startDate']) ?></p></div>
        <div class="date-year"><p><?= Date::parse('Y', $this->origin['startDate']) ?></p></div>
    </div>
    <?php endif; ?>
    <div class="entity-content">
        <p class="content-location"><?= $this->location ?></p>
        <p class="content-title"><?= $arrEvent['title'] ?></p>
        <?php if ($arrEvent['teaser']): ?>
        <div class="content-teaser">
            <p><?= strip_tags($arrEvent['teaser']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($strUrl): ?>
        <p class="content-url"><a href="<?= $strUrl ?>?date=<?= $this->id ?>">mehr Erfahren</a></p>
        <?php endif; ?>
    </div>
</div>
```

In diesem Beispiel-Template wird ein einzelner Event-Eintrag im Frontend dargestellt. Die Event-Daten werden mit Hilfe von Contao-Funktionen formatiert und ausgegeben. Das Template kann individuell angepasst werden, um den Anforderungen Deiner Website gerecht zu werden.

### Besondere Funktionen

#### Inhaltselemnte ausgeben

Wenn Du in Deinem Backend-Modul "Inhaltselemente" aktivierst, kannst Du für jeden Eintrag individuelle Inhaltselemente anlegen. Diese werden nicht standardmäßig im Template ausgegeben, sondern über eine Funktion:

``` php
<?= $this->getContentElements() ?>
```

Füge diese Funktion in Dein Template ein, um die Inhaltselemente anzuzeigen.

#### Merkliste

Deine Einträge können in eine Merkliste eingetragen werden. Füge dazu das folgende Formular in Dein Template ein:

``` php
<?php 
    use Alnv\ContaoCatalogManagerBundle\Library\Watchlist;
?>
<?= \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getForm($this->id, $this->_table, [
    'useUnits' => true,          // Mengenauswahl anzeigen
    'title' => $this->title,     // Titel für Screenreader (unterscheidet mehrere Buttons auf einer Seite)
    // 'disableCss' => true,     // eigenes Styling statt css/watchlist.scss
]); ?>
```

Als erster Parameter muss die ID des Datensatzes eingegeben werden, und als zweiter Parameter die Tabelle.

#### Kind-Einträge ausgeben

Über die $this->getRelated('myfieldname') kannst du auf Kind-Elemente zugreifen. Als Rückgabewert erhältst du ein Array, das du mit einer foreach-Schleife durchlaufen und ausgeben kannst. Die einzelnen Datensätze haben wiederum die gleichen Template-Funktionen wie das Eltern-Element.

Hier ist ein Beispiel, wie du darauf zugreifen kannst:

``` php
<?php 
$arrEntities = $this->getRelated('myfieldname');
?>
<?php foreach($arrEntities as $arrEntity): ?>
    <div class="child-entity">
        <h3><?= $arrEntity['title'] ?></h3>
        <p><?= $arrEntity['description'] ?></p>
        <!-- Weitere Felder entsprechend ausgeben -->
    </div>
<?php endforeach; ?>
```

In diesem Beispiel wird das Array $arrEntities mit den Kind-Elementen des Feldes "myfieldname" durchlaufen und jedes Kind-Element mit seinen Feldern wie "title" und "description" ausgegeben. Du kannst dieses Muster anpassen und weitere Felder entsprechend deiner Anforderungen einfügen.

Mit ``` $this->getParent() ``` kannst du auf das Elternelement zugreifen.

### Sitemap

Wenn deine Einträge in der Sitemap erscheinen sollen, musst du dein Katalog unbedingt in einem Frontend-Modul auswählen und dort eine Detailseite definieren.

### Detailseite

Für die Detailansicht steht nur ein Frontend-Modul zur Verfügung. Dieses besteht aus zwei Templates: mod_master und cm_master_*. Im cm_master_* gelten die gleichen Regeln und Funktionen wie in der Listenansicht (cm_listing_*).

**Du kannst die Liste und Detailansicht auf derselben Seite anzeigen. Nutze die Funktion "Element verstecken", um jeweils die passenden Module für die Listenansicht und Detailansicht zu steuern. Siehe Screenshot.**

![](https://catalog-manager.org/files/docs/screenshot-hi.png)

### Filter

In der Listenansicht und in einigen anderen Modulen gibt es ein Widget für den Filter. Siehe Screenshot. 
Vereinfacht gesagt kannst du in diesem Widget die Werte eintragen, nach denen du filtern möchtest, entweder als "Klartext" oder "Inserttag". Achte darauf, dass der Feldwert, der tatsächlich in der Datenbank gespeichert ist, mit dem abgefragten Wert übereinstimmt. Außerdem beachte den Operator, der grundsätzlich gilt:

- Bei Zahlen: equal, lower ...
- Bei Mehrfachauswahl: REGEXP, FIND_IN_SET

![](https://catalog-manager.org/files/docs/screenshot-1-filter.png)

Wenn du einen Filter erstellen möchtest, benötigst du im Grunde ein Modul, das die gefilterten Parameter als GET-Parameter an den Client übergibt, z.B. https://catalog-manager.org/meine-seite/?category=123. Den GET-Parameter "category" kannst du dann mit {{ACTIVE::category}} auslesen und in den Filtereinstellungen verwenden. Als Modul für den Filter kannst du den Formulargenerator (Formulare) von Contao verwenden oder dein eigenes Formular erstellen.

### Entwickler:innen

#### JSON und AJAX

Du kannst die Frontend-Module auch im JSON-Format erhalten, indem du einen POST-Request an folgende URL sendest: `` /catalog-manager/json-listing/<MODULE-ID>/<PAGE-ID> ``

Als Parameter sind die ID des Frontend-Moduls (Listenansicht) und die Seiten-ID, von der der Request stammt, erforderlich. An die URL kannst du auch GET-Parameter anhängen, z.B. ?category=123.

Hier ein Beispiel:

``` js
function fetchCatalogData(url) {
    // Markiere den Ladevorgang als aktiv
    this.loading = true;

    // Falls keine URL übergeben wurde, wird eine Standard-URL verwendet
    if (!url) {
        url = '/catalog-manager/json-listing/<MODULE-ID>/<PAGE-ID>';
    }

    // AJAX-POST-Anfrage mit fetch API
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(this.params),
    })
    .then(response => response.json())
    .then(data => {
        // Verarbeite die Antwortdaten
        this.catalogs = this.parse(data.results); // Verarbeitung der Ergebnisse
        this.total = data.limit ? data.limit : 0; // Gesamtzahl der Ergebnisse
        this.pagination = data.pagination; // Pagination-Daten

        // Setze die Pagination nach einer kurzen Verzögerung
        setTimeout(() => {
            this.setPagination(); // Funktion zur Aktualisierung der Pagination
            this.loading = false; // Ladevorgang beenden
            this.initial = false; // Initialisierung beenden
        }, 50);
    })
    .catch(error => {
        console.error('Fehler bei der AJAX-Anfrage:', error);
        this.loading = false; // Ladevorgang beenden
    });
}
```

#### Listen 

Du kannst auch eine Liste mit paar Zeilen PHP-Code ausgeben.

``` php
<?php 

use Alnv\ContaoCatalogManagerBundle\Views\Listing;

$arrEntities = (new Listing('<DEINE-CM-TABELLE>', [
  'column' => ['pid=?'], // Hier kannst du deine Wunschabfrage hinterlegen.
  'value' => [$this->id], // Hier die Datenbankwerte je Abfrage.
  'order' => 'startDate DESC', // Reihenfolge
  'masterPage' => "<SEITEN-ID" // Optional
]))->parse();

<?php foreach ($arrEntities as $arrEntity): ?>
  <h3><?= $arrEntity['MY_FIELD'] ?></h3>
<?php endforeach; ?>
```


### Umkreissuche einrichten

Damit die Umkreissuche korrekt funktioniert, müssen folgende Schritte durchgeführt werden:

**1. Adressfelder im Katalog definieren:**

Dein Katalog benötigt spezifische Adressfelder wie Postleitzahl oder Ort und Land. Optional können auch Straße und Hausnummer hinzugefügt werden.
Weisen Sie den einzelnen Feldern die passenden Rollen zu: street (Straße), streetNumber (Hausnummer), city (Ort), zip (Postleitzahl), postal (Postleitzahl), state (Bundesland), country (Land).

**2. GEO-Coding-Lizenzschlüssel erstellen:**

Erstelle einen GEO-Coding-Lizenzschlüssel bei Google. Dies kann über den folgenden Link erfolgen: https://console.cloud.google.com/apis/dashboard

**3. Formular mit Adressfeldern erstellen:**

Erstelle ein Formular, das deine Adressfelder enthält: 

Dazu gehören mindestens Postleitzahl oder Ort sowie Land. Optional können Straße und Hausnummer hinzugefügt werden.
Verwende die folgenden Feldnamen:
rs_pstl für Postleitzahl
rs_cty für Ort
rs_strt für Straße + Hausnummer
rs_cntry für Land
rs_dstnc für Distanz
Umkreis der Suche definieren:

Im Feld rs_dstnc kann der Suchradius definiert werden. Hierbei werden nur Zahlen akzeptiert.

### v1 und v3 Änderungen 

- {{CTLG_ACTIVE}} Inserttag wird zu {{ACTIVE}}
- {{CTLG_MASTER}} Inserttag wird zu {{MASTER}} und keine Einstellungen mehr in der Seitenstruktur erforderlich.
- {{CTLG_TIMESTAMP}} wird zu {{TIMESTAMP}} z.B. {{TIMESTAMP::tstamp::+ 1 days}}
- Es gibt kein Filterformular mehr, stattdessen kann der Formulargenerator verwendet werden.
- Frontend-Editing nicht verfügbar (kommt später).
- Modifizierer nicht verfügbar (kommt später).
- Umkreissuche hat neue Feldnamen:
    - rs_pstl wird zu postal
    - rs_cty wird zu city
    - rs_strt wird zu street
    - rs_cntry wird zu country
    - rs_dstnc wird zu radius

---

## MCP-Server für den Catalog-Manager v4 (Contao 5.7)

Mit diesem Server kann Claude den Catalog-Manager direkt bearbeiten:

- **Kataloge, Felder, Optionen, Paletten** anlegen, lesen, ändern, löschen
- **Katalogeinträge** (die Datensätze) verwalten
- **Frontend-Ausgabe**: Listen-, Detail- und Karten-Module sowie das Inhaltselement „listview“
- **Templates** (`cm_listing_*`, `cm_master_*` usw.) im Ordner `/templates` erstellen

Alle Änderungen laufen durch dieselbe Logik wie im Contao-Backend. Die `save_callback`, `onsubmit_callback`, `oncreate_callback` und `ondelete_callback` aus den DCA-Dateien werden in derselben Reihenfolge ausgeführt. Legt Claude einen Katalog an, entsteht also auch die Datenbanktabelle. Ein neues Feld bekommt seine Spalte, beim Umbenennen wird die Spalte umbenannt.

Der Server ist ein Konsolen-Befehl (`catalog:mcp`) in deinem Contao-Projekt. Claude Desktop startet ihn per SSH und spricht über stdin/stdout mit ihm. Du brauchst keinen offenen Port und keinen Webserver-Endpunkt.

### 0. Paket installieren (optional seit 4.1)

`php-mcp/server` ist keine Pflicht-Abhängigkeit mehr. Ohne das Paket wird der Befehl `catalog:mcp` nicht registriert. Wer den MCP-Server nutzen möchte, installiert es im Projekt:

```bash
composer require php-mcp/server:^3.3
```

### 1. Mit Claude Desktop verbinden

Claude Desktop → Einstellungen → Entwickler → Konfiguration bearbeiten (`claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "contao-catalog": {
      "command": "ssh",
      "args": [
        "-T",
        "user@dein-server.de",
        "cd /pfad/zum/contao && php vendor/bin/contao-console catalog:mcp --env=prod"
      ]
    }
  }
}
```

Danach Claude Desktop neu starten. Unter den Werkzeugen erscheint „contao-catalog“.

Voraussetzungen für SSH:

- Anmeldung per **SSH-Schlüssel ohne Passwortabfrage**. Claude Desktop kann kein Passwort eintippen.
- Die Shell auf dem Server darf beim Login **nichts auf stdout ausgeben**, z. B. kein `echo` in `.bashrc`. Sonst stört das das Protokoll.

**Nur lesen:** Zum Ausprobieren `--read-only` anhängen. Dann stehen nur die lesenden Werkzeuge zur Verfügung.

**Claude Code** (Terminal):

```bash
claude mcp add contao-catalog -- ssh -T user@dein-server.de "cd /pfad/zum/contao && php vendor/bin/contao-console catalog:mcp --env=prod"
```

### 2. Die Werkzeuge

| Werkzeug | Zweck |
|---|---|
| `catalog_overview` | Alle Kataloge mit Tabelle, Feldern, Anzahl Einträge, dazu die Module und Inhaltselemente |
| `config_describe` | **Konfigurationsmöglichkeiten** einer Tabelle: aktive Felder, Pflichtfelder, erlaubte Optionen. Mit `context` eine andere Palette wählen, z. B. `{"type":"select"}` |
| `record_list` / `record_get` | Datensätze lesen (Filter, Sortierung, Spaltenauswahl) |
| `record_create` | Anlegen (mit `pid` für Felder, Optionen, Paletten; mit `pid` + `ptable` für Inhaltselemente) |
| `record_update` | Felder ändern |
| `record_delete` | Löschen, ohne `confirm=true` nur Vorschau. Vorher wird eine JSON-Sicherung angelegt |
| `template_list` / `template_read` | Eigene Templates und Standard-Templates des Bundles |
| `template_context` | Felder, Rollen und Template-Variablen eines Katalogs, damit Templates passen |
| `template_write` / `template_delete` | Template schreiben (mit PHP-Syntaxprüfung) bzw. entfernen (mit Sicherung) |

**Freigegebene Tabellen:** `tl_catalog`, `tl_catalog_field`, `tl_catalog_option`, `tl_catalog_palette`, die Datentabellen der Kataloge sowie `tl_module` und `tl_content`. Bei den letzten beiden sind nur die Catalog-Manager-Typen erlaubt (`listing-table`, `master`, `listing-map`, `listview`).

## 3. Beispiele für Anfragen an Claude

- *„Zeig mir alle Kataloge und welche Felder sie haben.“*
- *„Welche Einstellungen gibt es für ein Auswahlfeld (select)?“*
- *„Leg einen Katalog ‚Veranstaltungen‘ mit der Tabelle tl_cm_events an, mit den Feldern Titel (Rolle title), Datum, Ort und Kategorie als Auswahl mit den Optionen Konzert, Lesung, Workshop.“*
- *„Erstelle ein Listen-Modul und ein Detail-Modul für die Veranstaltungen.“*
- *„Schreib ein Listen-Template cm_listing_events als Karte mit Datum, Titel und Ort und stell es im Listen-Modul ein.“*
- *„Leg drei Beispiel-Einträge an.“*

## 4. Sicherheit und Grenzen

- **Volle Datenbankrechte:** Der Server läuft mit den Datenbank-Zugangsdaten von Contao, ohne Backend-Benutzer und ohne Rechteprüfung. Wer den SSH-Zugang hat, kann alles, was die Werkzeuge erlauben.
- **Vorher sichern:** Erst auf einer Staging-Kopie oder mit einem aktuellen Datenbank-Backup ausprobieren.
- **Keine Versionierung:** Änderungen erzeugen keine Contao-Versionen (`tl_version`) und kein „Wiederherstellen“. Gelöschte Datensätze liegen als JSON in `var/catalog-mcp/backups/`, ersetzte oder gelöschte Templates in `var/catalog-mcp/templates/`.
- **onload_callbacks:** Sie werden nur für `tl_catalog_palette` ausgeführt, weil dort die Palette vom Datensatz abhängt. Die anderen brauchen einen angemeldeten Backend-Benutzer. Deshalb zeigt `config_describe` bei Katalogen vom Typ „Modifier“ (Erweiterung bestehender Tabellen) evtl. mehr Felder als das Backend.
- **Templates:** Nur `.html5`-Templates mit Catalog-Manager-Präfix. Erscheint ein neues Template im Backend nicht in der Auswahl, `cache:clear --env=prod` ausführen. Templates in Unterordnern müssen im Theme als Template-Ordner eingetragen sein.