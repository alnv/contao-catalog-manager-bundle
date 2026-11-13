# Catalog Manager für Contao 5

> **Wichtig:** Diese Erweiterung nicht zusammen mit dem Catalog Manager v1 installieren!

Der Catalog Manager (`alnv/catalog-manager-bundle`, Version 4.1) ist eine Erweiterung für Contao CMS 5.7. Mit ihm legst du eigene Datenstrukturen („Kataloge“) im Backend an, ohne PHP- oder DCA-Code zu schreiben, und gibst die Daten im Frontend als Listen, Detailseiten oder Karten aus. Er hat sich bereits in über 100 Contao-Projekten bewährt.

Du kannst eine kostenfreie und uneingeschränkte Testversion installieren und erst dann eine Lizenz kaufen, wenn dein Projekt fertig ist. Bis dahin unterstützen wir dich mit Beratung und Hilfe.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
   - [Einsatzbereiche](#einsatzbereiche)
   - [Funktionen auf einen Blick](#funktionen-auf-einen-blick)
   - [Architektur](#architektur)
   - [Projektstruktur](#projektstruktur)
2. [Installation und Konfiguration](#2-installation-und-konfiguration)
   - [Voraussetzungen](#voraussetzungen)
   - [Installation](#installation)
   - [Konfiguration](#konfiguration)
   - [Konfigurationsparameter und globale Einstellungen](#konfigurationsparameter-und-globale-einstellungen)
3. [Kataloge anlegen](#3-kataloge-anlegen)
   - [Katalog und Datentabelle](#katalog-und-datentabelle)
   - [Navigation im Backend](#navigation-im-backend)
   - [Kind-Kataloge](#kind-kataloge)
   - [Felder und Rollen](#felder-und-rollen)
   - [Fieldsets und Paletten](#fieldsets-und-paletten)
   - [Mehrsprachige Kataloge](#mehrsprachige-kataloge)
4. [Ausgabe im Frontend](#4-ausgabe-im-frontend)
   - [Listen (Modul und Inhaltselement)](#listen-modul-und-inhaltselement)
   - [Detailseite](#detailseite)
   - [Karte](#karte)
   - [Template-Variablen und -Funktionen](#template-variablen-und--funktionen)
   - [Filter](#filter)
   - [Seitentyp „Filter“](#seitentyp-filter)
   - [Umkreissuche](#umkreissuche)
   - [Merkliste](#merkliste)
   - [Reaktionen, Teilen-Buttons und iCalendar](#reaktionen-teilen-buttons-und-icalendar)
   - [Sitemap und Suche](#sitemap-und-suche)
   - [Insert-Tags](#insert-tags)
5. [Frontend-Verwaltung (Frontend-Manager)](#5-frontend-verwaltung-frontend-manager)
   - [Was die Frontend-Verwaltung leistet](#was-die-frontend-verwaltung-leistet)
   - [Einrichtung Schritt für Schritt](#einrichtung-schritt-für-schritt)
   - [Modul-Einstellungen im Detail](#modul-einstellungen-im-detail)
   - [Berechtigungen und Sicherheit](#berechtigungen-und-sicherheit)
   - [Formularfelder](#formularfelder)
   - [Datei-Uploads und Home-Verzeichnisse](#datei-uploads-und-home-verzeichnisse)
   - [Rich Text (TinyMCE)](#rich-text-tinymce)
   - [Datum und Uhrzeit](#datum-und-uhrzeit)
   - [JavaScript und AJAX](#javascript-und-ajax)
   - [Templates, Styling und Assets](#templates-styling-und-assets)
   - [Events für Entwickler:innen](#events-für-entwicklerinnen)
   - [Erweitern](#erweitern)
   - [Beispiele](#beispiele-für-die-frontend-verwaltung)
6. [MCP-Server (Model Context Protocol)](#6-mcp-server-model-context-protocol)
   - [Was ist MCP?](#was-ist-mcp)
   - [So ist MCP integriert](#so-ist-mcp-integriert)
   - [Einrichtung](#einrichtung)
   - [Clients verbinden](#clients-verbinden)
   - [Die Werkzeuge](#die-werkzeuge)
   - [Protokollbeispiele](#protokollbeispiele)
   - [Anwendungsbeispiele](#anwendungsbeispiele)
   - [Sicherheit und Grenzen](#sicherheit-und-grenzen)
7. [Für Entwickler:innen](#7-für-entwicklerinnen)
   - [Listen per PHP](#listen-per-php)
   - [JSON-Endpunkte (veraltet)](#json-endpunkte-veraltet)
   - [Hooks](#hooks)
   - [Eigene Rollen](#eigene-rollen)
   - [Tests](#tests)
8. [Praxisbeispiele](#8-praxisbeispiele)
9. [Häufige Probleme und Lösungen](#9-häufige-probleme-und-lösungen)
10. [Änderungen gegenüber v1](#10-änderungen-gegenüber-v1)
11. [Support](#11-support)

---

## 1. Überblick

### Einsatzbereiche

- **Umkreissuchen:** Ergebnisse in der Nähe finden, z. B. Händler oder Standorte.
- **Jobbörsen:** Stellenangebote verwalten und präsentieren.
- **Branchenbücher:** Verzeichnisse für verschiedene Branchen.
- **Mitgliederverwaltung:** Mitglieder und ihre Daten organisieren, auch mit Selbstverwaltung im Frontend.
- **Immobilienbörsen:** Immobilienangebote übersichtlich darstellen.
- **Suchen und Filtern:** Daten über URL-Parameter, Formulare oder Filterseiten einschränken.
- **Produktverwaltung:** Produkte mit Bildern, Preisen und Kategorien.
- **Merklisten und Wunschlisten:** Besucher speichern Favoriten.
- **Mehrsprachigkeit:** integriert über `terminal42/dc_multilingual`, siehe [Mehrsprachige Kataloge](#mehrsprachige-kataloge).

### Funktionen auf einen Blick

| Bereich | Funktion |
|---|---|
| **Backend** | Kataloge mit Feldern, Optionen und Paletten anlegen; die Datentabelle wird automatisch erzeugt und angepasst. Eigene Navigationspunkte, Eltern-Kind-Kataloge, Rollen für Feldtypen, Inhaltselemente pro Eintrag. |
| **Frontend-Ausgabe** | Frontend-Module „Liste“ (`listing-table`), „Detailansicht“ (`master`) und „Karte“ (`listing-map`), Inhaltselement „Liste“ (`listview`), Seitentyp „Filter“. |
| **Filter und Suche** | Filter-Wizard oder Experten-Filter mit Insert-Tags, Umkreissuche mit Google Geocoding, Gruppierung, Sortierung, Paginierung. |
| **Interaktion** | Merkliste, Reaktionen, Teilen-Buttons, iCalendar-Export. |
| **Frontend-Verwaltung** | Angemeldete Mitglieder legen ihre Datensätze im Frontend an und bearbeiten sie, mit Uploads, Rich Text und nativen Datumsfeldern; Events für Benachrichtigungen und Schnittstellen (seit 4.1). |
| **MCP-Server** | KI-Assistenten wie Claude legen Kataloge, Felder, Einträge, Module und Templates über das Model Context Protocol an (optional). |
| **Integration** | Sitemap, Contao-Backend-Suche, Breadcrumb, Metadaten der Detailseite, Hooks zur Erweiterung. |

### Architektur

```text
                         ┌──────────────────────────────┐
  Backend (Contao)       │ tl_catalog, tl_catalog_field,│   Konfiguration der Kataloge
  ─────────────────────► │ tl_catalog_option,           │
                         │ tl_catalog_palette           │
                         └──────────────┬───────────────┘
                                        │ CatalogWizard / VirtualDataContainerArray
                                        ▼
                         ┌──────────────────────────────┐
                         │ Virtuelles DCA je Katalog     │   $GLOBALS['TL_DCA']['<tabelle>']
                         │ + Datentabelle (z. B. cm_events)│  Spalten über Rollen (SQL, rgxp …)
                         └───┬───────────┬───────────┬──┘
                             │           │           │
            ┌────────────────┘           │           └─────────────────┐
            ▼                            ▼                             ▼
  Views (Listing, Master)      FrontendManager (Modul)        MCP-Server (catalog:mcp)
  Module, Inhaltselement,      DcFormBuilder → RecordService  Tools → DcaRecordManager
  Karte, JSON                  → DcaRecordManager             (stdio, CLI)
            │                            │                             │
            ▼                            ▼                             ▼
  Templates cm_listing_*,      Templates mod_catalog_          KI-Client (Claude Desktop,
  cm_master_*, cm_map_view_*   frontend_manager, ce_dc_form    Claude Code …)
```

Das Zusammenspiel:

1. **Konfiguration:** Kataloge, Felder, Optionen und Paletten liegen in den `tl_catalog*`-Tabellen. Callbacks legen beim Speichern die Datentabelle und ihre Spalten an, benennen sie um oder löschen sie.
2. **Virtuelles DCA:** Zur Laufzeit erzeugt der Catalog Manager aus dieser Konfiguration ein normales Contao-DCA (`VirtualDataContainerArray`). Backend, Frontend-Verwaltung und MCP arbeiten deshalb mit denselben Feldern, Paletten und Callbacks.
3. **Rollen:** Jedes Feld hat eine Rolle, z. B. `title`, `image` oder `startDate`. Die Rolle legt SQL-Typ, Validierung und Bedeutung fest. Über den `RoleResolver` finden Templates, Karte, Umkreissuche, Sitemap und Frontend-Verwaltung die passenden Felder, ohne Feldnamen zu kennen.
4. **Ausgabe:** Die Views `Listing` und `Master` lesen Einträge über ein dynamisches Model (`DynModel`), bereiten sie auf (Bilder, Optionen, Detail-URL, Relationen) und reichen sie an Templates weiter.
5. **Schreiben außerhalb des Backends:** Die Frontend-Verwaltung und der MCP-Server speichern beide über den `DcaRecordManager`. Er führt die DCA-Callbacks (`save_callback`, `onsubmit_callback`, `oncreate_callback`, `ondelete_callback`) wie das Backend aus.

### Projektstruktur

```text
contao-catalog-manager-bundle/
├── composer.json                  Paketdefinition, Abhängigkeiten, Manager-Plugin
├── phpunit.xml.dist               Unit-Tests (Frontend-Verwaltung)
├── tests/FrontendManager/         Tests für Zugriff, Felder, Uploads, Datumswerte, AJAX-Antworten
└── src/
    ├── ContaoManager/Plugin.php   Registrierung im Contao Manager
    ├── DependencyInjection/       Lädt services.yml; registriert catalog:mcp nur mit php-mcp/server
    ├── Controller/                FrontendController (JSON, Merkliste, iCalendar), BackendOptionController
    ├── DataContainer/             Backend-Callbacks (Kataloge, Felder, Module, Frontend-Verwaltung)
    ├── Elements/                  Inhaltselement „listview“
    ├── Event/FrontendManager/     Events der Frontend-Verwaltung (anlegen, bearbeiten, löschen, Sichtbarkeit)
    ├── EventListener/             Contao-Hooks (Insert-Tags, Sitemap, Breadcrumb, Seiten-Layout …)
    ├── Helper/                    CatalogWizard, Toolkit, CatalogTableGuard, ModelWizard …
    ├── Inserttags/                {{ACTIVE}}, {{MASTER}}, {{TIMESTAMP}}, {{WATCHLIST}} …
    ├── Library/
    │   ├── DcFormBuilder.php      Frontend-Formulare aus dem DCA
    │   ├── DcFormBuilder/         Uploads, Upload-Ziele, Datumsspeicherung
    │   ├── FrontendManager/       Konfiguration, Berechtigungen, Datensatzzugriff, AJAX-Antworten
    │   ├── RoleResolver.php       Felder über Rollen finden
    │   ├── VirtualDataContainerArray.php  Virtuelles DCA je Katalog
    │   └── Watchlist.php, Reactions.php, ShareButtons.php, ICalendar.php …
    ├── Maps/                      Kartenansicht
    ├── Mcp/                       MCP-Server: Befehl, Werkzeuge, DcaRecordManager, TemplateManager
    ├── Models/                    Models inkl. DynModel/MultilingualDynModel für beliebige Katalogtabellen
    ├── Modules/                   Frontend-Module (Liste, Detail, Karte, Frontend-Verwaltung)
    ├── Pages/                     Seitentyp „Filter“
    ├── Search/                    Anbindung an die Contao-Backend-Suche
    ├── Views/                     Listing, Master, View (Aufbereitung der Einträge)
    ├── Widgets/                   CustomOptionWizard (Backend), FormDateTime (Frontend)
    └── Resources/
        ├── config/                services.yml, routing.yml
        ├── contao/                config, dca, languages, templates
        └── public/                css (SCSS), js (frontend-manager.js, Vue-Komponenten), Icons
```

---

## 2. Installation und Konfiguration

### Voraussetzungen

| Komponente | Version |
|---|---|
| PHP | ^8.3 |
| Contao | ~5.7 (`contao/core-bundle`) |
| Datenbank | MySQL bzw. MariaDB, wie von Contao 5.7 unterstützt |

Diese Pakete werden automatisch mitinstalliert (`composer.json`):

| Paket | Zweck |
|---|---|
| `terminal42/dcawizard` | Assistenten im Backend (z. B. Optionen) |
| `alnv/translation-manager-bundle` | Übersetzungen |
| `alnv/widget-collection-bundle` | Backend-Widgets |
| `terminal42/dc_multilingual` | Mehrsprachige Kataloge (DataContainer „Multilingual“) |
| `terminal42/contao-changelanguage` | Sprachwechsel auf Detailseiten mehrsprachiger Kataloge |
| `alnv/contao-geocoding` | Geocoding für Umkreissuche und Karten |
| `guzzlehttp/guzzle` (`^7.10 \|\| ^8.3`) | HTTP-Anfrage der Lizenzprüfung. Kompatibel mit Guzzle 7 und 8; welche Version installiert wird, bestimmen die übrigen Pakete (Contao 5.7 lässt derzeit nur Guzzle 7 zu). |
| `symfony/rate-limiter` | Begrenzung für Merkliste und Reaktionen |

Optional:

| Paket | Zweck |
|---|---|
| `php-mcp/server` ^3.3 | Aktiviert den MCP-Server (`catalog:mcp`), siehe [Abschnitt 6](#6-mcp-server-model-context-protocol) |

Für die Frontend-Verwaltung brauchst du **keine** Node.js-, npm- oder Build-Werkzeuge. JavaScript und SCSS werden zur Laufzeit von Contao eingebunden und kompiliert (siehe [Templates, Styling und Assets](#templates-styling-und-assets)).

### Installation

**Mit dem Contao Manager:** Nach `alnv/catalog-manager-bundle` suchen, installieren und danach die Datenbank aktualisieren.

**Mit Composer:**

```bash
composer require alnv/catalog-manager-bundle
php vendor/bin/contao-console contao:migrate
php vendor/bin/contao-console cache:clear --env=prod
```

`contao:migrate` legt die Tabellen des Bundles an (`tl_catalog`, `tl_catalog_field`, `tl_watchlist` …) und ergänzt die Felder in `tl_module`, `tl_content` und `tl_page`. Führe den Befehl nach **jedem Update** erneut aus. Seit 4.1 entfernt er zum Beispiel alte Felder der Frontend-Verwaltung (`cmFmGroups`, `cmFmUploadFolder` …) und legt `cmFmUseHomeDir` an.

**MCP-Server (optional):**

```bash
composer require php-mcp/server:^3.3
php vendor/bin/contao-console cache:clear --env=prod
php vendor/bin/contao-console list catalog     # zeigt jetzt catalog:mcp
```

### Konfiguration

| Was | Wo | Wofür |
|---|---|---|
| Lizenz | Backend → Catalog Manager → Lizenz (`tl_catalog_license`) | Lizenzschlüssel nach Projektabschluss |
| Google-API-Schlüssel | Backend → System → Einstellungen → „Karten-Einstellungen“ (`googleMapsApiKey`) | Geocoding (Umkreissuche, Koordinaten) und Karten |
| Datums- und Zeitformate | Einstellungen (`dateFormat`, `timeFormat`, `datimFormat`), je Website auch im Startpunkt | Anzeige und Eingabe von Datumswerten |
| Upload-Typen und -Größe | Einstellungen (`uploadTypes`, `maxFileSize`, `imageWidth`, `imageHeight`) | Fallback für Uploads der Frontend-Verwaltung |
| Upload-Standardordner der Frontend-Verwaltung | `config/parameters.yaml` | siehe nächster Abschnitt |
| Freigaben und Erweiterungen | `contao/config/config.php` des Projekts | z. B. Contao-Tabellen für die Frontend-Verwaltung, eigene Rollen, Navigation |

### Konfigurationsparameter und globale Einstellungen

**Symfony-Parameter** (in `src/Resources/config/services.yml` vorbelegt, im Projekt in `config/parameters.yaml` überschreibbar):

| Parameter | Standard | Bedeutung |
|---|---|---|
| `catalog_manager.frontend_manager.upload_folder` | `%contao.upload_path%/catalog-manager/uploads` | Upload-Ordner der Frontend-Verwaltung, wenn das Feld keinen Ordner festlegt |
| `catalog_manager.frontend_manager.home_folder` | `%contao.upload_path%/catalog-manager/members` | Basisordner für automatisch angelegte Home-Verzeichnisse (`user_<ID>`) |
| `catalog_manager.frontend_manager.create_folders` | `true` | Fehlende Upload- und Home-Ordner beim ersten Upload anlegen |

```yaml
# config/parameters.yaml
parameters:
  catalog_manager.frontend_manager.upload_folder: 'files/uploads/catalog'
  catalog_manager.frontend_manager.home_folder: 'files/members'
  catalog_manager.frontend_manager.create_folders: true
```

Alle Pfade müssen innerhalb von `%contao.upload_path%` (meist `files`) liegen.

**Globale PHP-Konfiguration** (in `contao/config/config.php` des Projekts):

| Schlüssel | Bedeutung |
|---|---|
| `$GLOBALS['CM_FRONTEND_MANAGER']['tables'][]` | Contao-Tabellen (`tl_*`) für die Frontend-Verwaltung freigeben |
| `$GLOBALS['CM_FRONTEND_MANAGER']['widgets'][<inputType>]` | DCA-Eingabetyp einem Frontend-Formularfeld (`TL_FFL`) zuordnen |
| `$GLOBALS['CM_ROLES'][<rolle>]` | Eigene Rollen per Code (Alternative zum Backend-Modul „Rollen“) |
| `$GLOBALS['CM_OPERATORS']` | SQL-Operatoren des Filter-Wizards (`equal`, `findInSet`, `regexp`, `between`, `isEmpty` …) |
| `$GLOBALS['TL_HOOKS'][…]` | Hooks des Catalog Managers, siehe [Hooks](#hooks) |

---

## 3. Kataloge anlegen

### Katalog und Datentabelle

Kataloge legst du unter **Catalog Manager** im Backend an. Ein Katalog vom Typ „Katalog“ hat unter anderem diese Einstellungen:

- **Tabelle:** Name der Datentabelle, z. B. `cm_events`. Sie wird beim Speichern angelegt und beim Löschen des Katalogs entfernt.
- **Modus:** Listenansicht, Elternansicht oder Baumansicht im Backend, jeweils mit Sortierung und Spalten.
- **Erweiterte Einstellungen:** Kopieren, Sichtbarkeit (`published`), Filterpanel, Vorschau und Inhaltselemente pro Eintrag.
- **Navigation:** Unter welchem Backend-Menüpunkt der Katalog erscheint und an welcher Position.
- **Geocoding:** Koordinaten aus Adressfeldern automatisch ermitteln (für Karte und Umkreissuche).

### Navigation im Backend

Bei den Navigationseinstellungen kannst du nur bestehende Navigationspunkte auswählen. Einen eigenen Navigationspunkt deklarierst du in der `contao/config/config.php` deines Projekts:

```php
use Contao\ArrayUtil;

ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 1, [
    'my_catalogs' => []
]);
```

### Kind-Kataloge

Für eine Eltern-Kind-Beziehung legst du Kind-Kataloge als Unterpunkte des Eltern-Katalogs an, genau wie Unterseiten in der Seitenstruktur. Die Datentabelle des Kind-Katalogs erhält dann `pid` bzw. `ptable`. Im Template erreichst du den Eltern-Eintrag mit `$this->getParent()`.

> **Wichtig:** Sobald ein Katalog Datensätze enthält, lässt er sich nicht mehr verschieben.

### Felder und Rollen

Felder legst du über das Feld-Symbol eines Katalogs an. Verfügbare Feldtypen: `text`, `color`, `date`, `textarea`, `select`, `radio`, `checkbox`, `checkboxWizard`, `customOptionWizard`, `pagepicker`, `upload`, `explanation`, `empty` und `listWizard`.

Backend-CSS-Klassen, SQL-Datentypen und reguläre Ausdrücke definierst du **nicht direkt**, sondern über **Rollen**. Wähle deshalb für jedes Feld die passende Rolle:

| Rolle | Verwendung |
|---|---|
| `title` | Titel des Eintrags. Sollte jeder Katalog haben; wird für Seitentitel, Sitemap und die Überschrift der Frontend-Verwaltung verwendet. |
| `alias` | Alias-Feld (Feldname muss `alias` lauten); Grundlage der Detail-URL. |
| `textfield` | `varchar(255)` für alle Texte ohne spezielle Rolle. |
| `miscellaneous` | Binärer Typ (`blob NULL`) für Auswahllisten oder allgemeine Widgets. |
| `image`, `gallery`, `file`, `files` | Ein Bild, mehrere Bilder, eine Datei, mehrere Dateien. |
| `teaser`, `description`, `content` | Längere Texte. |
| `startDate`, `endDate`, `date` / `startTime`, `endTime`, `time` / `datim` | Datum (`rgxp=date`), Uhrzeit (`rgxp=time`), Datum und Uhrzeit (`rgxp=datim`); als Timestamp gespeichert. |
| `street`, `streetNumber`, `zip`, `postal`, `city`, `state`, `country`, `address` | Adressfelder für Geocoding und Umkreissuche. |
| `latitude`, `longitude` | Koordinaten. |
| `member`, `members`, `group`, `groups` | Bezug zu Mitgliedern bzw. Gruppen (z. B. als Besitzerfeld der Frontend-Verwaltung). |
| `hasone`, `hasmany` | Relationen zu anderen Tabellen (für `getRelated()`). |
| `email`, `url`, `phone`, `price`, `integer`, `decimal` … | Werte mit passender Validierung und passendem SQL-Typ. |

Die vollständige Liste steht in `$GLOBALS['CM_ROLES']` (`src/Resources/contao/config/config.php`). Eigene Rollen legst du im Backend-Modul **Rollen** an, siehe [Eigene Rollen](#eigene-rollen).

Weitere Feldeinstellungen, die auch die Frontend-Verwaltung übernimmt:

- **Pflichtfeld**, **Mehrfachauswahl**, **Leere Option**
- **Rich Text Editor** und Editor-Typ, z. B. `tinyMCE`
- Bei Upload-Feldern: **Dateitypen**, **Upload-Ordner**, **Home-Verzeichnis verwenden**, **Nicht überschreiben**, **Bildbreite**, **Bildhöhe**

### Fieldsets und Paletten

Einen Katalog kannst du über **Paletten bearbeiten** (grünes Symbol, drittes von links) in Fieldsets, Subpaletten oder Typen unterteilen:

![](https://catalog-manager.org/files/docs/screenshot-pal-1.png)
![](https://catalog-manager.org/files/docs/screenshot-pal-2.png)

Die Palette bestimmt die Reihenfolge und Gruppierung der Felder im Backend. Die Frontend-Verwaltung übernimmt sie einschließlich der Subpaletten.

### Mehrsprachige Kataloge

Mehrsprachigkeit ist Teil des Bundles (früher `alnv/catalog-manager-multilingual-adapter-bundle`). Sie baut auf `terminal42/dc_multilingual` auf: Übersetzungen liegen als eigene Datensätze in derselben Tabelle und verweisen auf den Datensatz der Fallback-Sprache.

1. Im Katalog als **DataContainer** „Multilingual“ wählen. Die Tabelle erhält die Spalten `lid` (ID des Fallback-Datensatzes) und `language` (Sprache der Übersetzung).
2. In jedem Feld, das übersetzt werden soll, unter **Mehrsprachige-Einstellungen → Übersetzbar für** die Sprachen wählen; `*` steht für alle Sprachen. Zur Auswahl stehen die Sprachen der Startpunkte.
3. Die Fallback-Sprache ist die Sprache des Startpunkts mit „Sprachen-Fallback“. Sind mehrere Startpunkte als Fallback markiert, wird keine Fallback-Sprache gesetzt.

Was danach automatisch passiert:

- Das Backend nutzt den Treiber von dc_multilingual (`Terminal42\DcMultilingualBundle\Driver`) mit `langPid = lid`, `langColumnName = language` und mehrsprachigen Aliasen.
- Listen, Detailseiten, Insert-Tags und Zählungen lesen über `MultilingualDynModel`: Felder der aktuellen Sprache, sonst Fallback. Detailseiten finden einen Eintrag über den Alias der Fallback-Sprache oder der aktuellen Sprache.
- Im Filter-Wizard stehen übersetzbare Felder zusätzlich als `translation.<feld>` zur Verfügung.
- Mit `terminal42/contao-changelanguage` verlinkt der Sprachwechsel auf Detailseiten den Alias des Eintrags in der Zielsprache.

> **Umstieg vom Adapter-Bundle:** `alnv/catalog-manager-multilingual-adapter-bundle` aus der `composer.json` des Projekts entfernen und `composer update` ausführen. Das Paket wird per `replace` ersetzt; Datenbank und Katalogeinstellungen bleiben unverändert. Eigener Code, der `Alnv\ContaoCatalogManagerMultilingualAdapterBundle\Models\MultilingualDynModel` verwendet, muss auf `Alnv\ContaoCatalogManagerBundle\Models\MultilingualDynModel` umgestellt werden. Die Service-IDs `catalogmanagermultilingualadapter.*` gibt es nicht mehr.

---

## 4. Ausgabe im Frontend

### Listen (Modul und Inhaltselement)

Für alle Kataloge (genauer: für alle Tabellen) gibt es zwei Arten von Listen:

| Typ | Name | Templates |
|---|---|---|
| Frontend-Modul | `listing-table` | Hülle `mod_listing_table`, je Eintrag `cm_listing_*` (Feld „Template“) |
| Inhaltselement | `listview` | `ce_listview_*`, alle Einträge in `$this->entities` |

Die wichtigsten Einstellungen beider Typen:

| Einstellung | Wirkung |
|---|---|
| Tabelle | Welcher Katalog ausgegeben wird |
| Detailseite | Seite mit dem Detail-Modul; daraus entsteht `masterUrl` |
| Filter | Einträge einschränken, siehe [Filter](#filter) |
| Paginierung, Limit, Offset | Anzahl und Seiten |
| Gruppieren, Gruppen-Überschrift | Nur beim Modul |
| Sortierung | Reihenfolge |
| Umkreissuche | Siehe [Umkreissuche](#umkreissuche) |
| Felder nicht aufbereiten | Performance: Felder überspringen, die das Template nicht braucht |

Es gibt kein Backend-Formular für die Gestaltung: Die Ausgabe erfolgt über Templates. **Inhaltselement `ce_listview_*`**, alle Einträge in einer Schleife:

```php
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

**Modul, Template `cm_listing_*`** pro Eintrag. Das Beispiel stammt aus einem Spielplan: Der Eintrag ist eine Aufführung, `getParent()` liefert die Veranstaltung.

```php
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

Als Vorlage dienen die mitgelieferten Templates `cm_listing_default` und `cm_master_default`. Eigene Templates legst du wie in Contao üblich unter `/templates` an.

### Detailseite

Für die Detailansicht gibt es das Frontend-Modul **Detailansicht** (`master`) mit den Templates `mod_master` (Hülle) und `cm_master_*` (Eintrag). In `cm_master_*` stehen dieselben Variablen und Funktionen wie in `cm_listing_*` zur Verfügung. Der Eintrag wird über den Alias in der URL (`auto_item`) bestimmt. Auf der Detailseite setzt der Catalog Manager den Seitentitel aus der Rolle `metaTitle` (sonst `title`), die Beschreibung aus `metaDescription` (sonst `description`) und ergänzt die Breadcrumb; außerdem liefert dort das Insert-Tag `{{MASTER::…}}` Werte (siehe [Insert-Tags](#insert-tags)).

**Liste und Detailansicht auf derselben Seite:** Mit „Element verstecken“ steuerst du, welches Modul in der Listenansicht und welches in der Detailansicht erscheint:

![](https://catalog-manager.org/files/docs/screenshot-hi.png)

### Karte

Das Modul **Karte** (`listing-map`) zeigt Einträge mit den Rollen `latitude` und `longitude` auf einer Google-Karte (Template `cm_map_view_gmap`). Es braucht den Google-API-Schlüssel aus den Einstellungen. Den Inhalt der Info-Fenster legst du im Modul fest (`cmInfoContent`).

### Template-Variablen und -Funktionen

Jeder Eintrag in `cm_listing_*`, `cm_master_*` und `$this->entities` enthält die aufbereiteten Feldwerte (Optionen als Bezeichnung, Bilder als Array), dazu diese Schlüssel:

| Schlüssel / Funktion | Inhalt |
|---|---|
| `origin` | Rohwerte aus der Datenbank, z. B. Timestamps: `$this->origin['startDate']` |
| `masterUrl` | URL der Detailseite |
| `getParent()` | Eltern-Eintrag (Kind-Kataloge) |
| `getRelated('feld')` | Verknüpfte Einträge eines Relationsfeldes; jedes Element hat wieder dieselben Funktionen |
| `getContentElements()` | Inhaltselemente des Eintrags (Option „Inhaltselemente“ im Katalog) |
| `roleResolver()` | `RoleResolver`, z. B. `$this->roleResolver()->getValueByRole('title')` |
| `parseImage($wert)` | Bildwert in HTML umwandeln |
| `parseArray($wert, ', ', 'label')` | Mehrfachwerte als Text |
| `shareButtons()` | Teilen-Buttons |
| `iCalendarUrl()` | Link zum iCalendar-Export |

Inhaltselemente eines Eintrags:

```php
<?= $this->getContentElements() ?>
```

Kind-Einträge eines Relationsfeldes:

```php
<?php $arrEntities = $this->getRelated('myfieldname'); ?>
<?php foreach ($arrEntities as $arrEntity): ?>
    <div class="child-entity">
        <h3><?= $arrEntity['title'] ?></h3>
        <p><?= $arrEntity['description'] ?></p>
    </div>
<?php endforeach; ?>
```

### Filter

Listen, Inhaltselement, Karte, Detailansicht und Frontend-Verwaltung haben ein Filter-Widget mit zwei Varianten:

- **Wizard:** Feld, Operator und Wert auswählen. Werte als Klartext oder Insert-Tag.
- **Experte:** Spalten und Werte direkt eintragen, jeweils durch `;` getrennt. Ist ein Wert leer, entfällt die zugehörige Bedingung. So bleiben optionale Filter aus, solange kein Parameter gesetzt ist.

![](https://catalog-manager.org/files/docs/screenshot-1-filter.png)

Der Wert in der Datenbank muss zum abgefragten Wert passen. Beachte den Operator:

- **Zahlen:** `equal`, `greater`, `lower`, `between` …
- **Mehrfachauswahl:** `regexp`, `findInSet`

**Experten-Filter mit URL-Parametern:**

```text
Spalten: category=?;city=?
Werte:   {{ACTIVE::category}};{{ACTIVE::city}}
```

Die Werte kommen aus der URL, z. B. `https://example.org/meine-seite/?category=123`. Ein Formular für solche Parameter baust du mit dem Formulargenerator (Methode GET) oder mit einem eigenen Formular.

### Seitentyp „Filter“

Neben GET-Parametern gibt es den Seitentyp **Filter**. Pfadsegmente nach dem Seiten-Alias werden dort als Filterwerte gelesen, z. B. `/immobilien/berlin/wohnung`. Jedes Segment entspricht einem Seitenfilter (`tl_page_filter`: Quelle, Tabelle, Spalte, Alias). Über den Alias liest du den Wert mit `{{ACTIVE::<alias>}}`. Ein Segment ohne passenden Eintrag führt zu einer 404-Seite; das verbleibende Segment wird zum `auto_item` (Detailansicht).

### Umkreissuche

1. **Adressfelder im Katalog:** Mindestens Postleitzahl oder Ort sowie Land, optional Straße und Hausnummer. Rollen: `street`, `streetNumber`, `zip` bzw. `postal`, `city`, `state`, `country`. Dazu kommen Felder mit den Rollen `latitude` und `longitude`; „Geocoding“ im Katalog füllt sie automatisch.
2. **Google-API-Schlüssel** in den Einstellungen hinterlegen (erstellen unter <https://console.cloud.google.com/apis/dashboard>).
3. **Umkreissuche** im Listen- bzw. Karten-Modul aktivieren.
4. **Suchformular** (Methode GET) mit diesen Feldnamen:

   | Feldname | Inhalt |
   |---|---|
   | `zip` oder `postal` | Postleitzahl |
   | `city` | Ort |
   | `street`, `streetNumber` | Straße, Hausnummer |
   | `state`, `country` | Bundesland, Land |
   | `radius` | Radius in km, nur Zahlen; Standard 15 |

Gesucht wird, sobald mindestens Straße, Hausnummer, Postleitzahl oder Ort gesetzt ist. Die Ergebnisse sind nach Entfernung sortiert.

### Merkliste

Formular zum Merken in `cm_listing_*` oder `cm_master_*` einfügen:

```php
<?= \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getForm($this->id, $this->_table, [
    'useUnits' => true,          // Mengenauswahl anzeigen
    'title' => $this->title,     // Titel für Screenreader (unterscheidet mehrere Buttons auf einer Seite)
    // 'disableCss' => true,     // eigenes Styling statt css/watchlist.scss
]); ?>
```

Erster Parameter ist die ID des Datensatzes, zweiter die Tabelle. Die Merkliste ist an die Sitzung gebunden, auf 200 Einträge begrenzt und gegen Massenanfragen geschützt (30 Änderungen pro Minute, CSRF-Token). Ausgabe über Insert-Tags:

| Insert-Tag | Ausgabe |
|---|---|
| `{{WATCHLIST-COUNT}}` | Anzahl der Einträge; `{{WATCHLIST-COUNT::?total=1}}` summiert die Mengen |
| `{{WATCHLIST-TABLE}}` | Tabelle der gemerkten Einträge, Template `ce_watchlist_table` (`?template=…` für ein eigenes) |
| `{{WATCHLIST}}` | IDs der gemerkten Einträge, z. B. als Filterwert; `?tables=cm_products` schränkt ein |
| `{{WATCHLIST-RESET}}` | Merkliste abschließen: Einträge der Sitzung werden als versendet markiert, z. B. auf der Danke-Seite nach einer Anfrage |

Gemerkte Einträge sind im Backend unter **Merkliste** einsehbar.

### Reaktionen, Teilen-Buttons und iCalendar

**Reaktionen** (z. B. „Gefällt mir“) definierst du im Backend unter **Reaktionen** (Schlüssel, Name, Icon, Template). Ausgabe im Template:

```php
<?= (new \Alnv\ContaoCatalogManagerBundle\Library\Reactions($this->_table, 1))->getReactions($this->id) ?>
```

Der zweite Parameter ist die ID der Reaktions-Definition. Abgegeben werden Reaktionen per POST-Formular; erlaubt sind 20 pro Minute und IP. Dieselbe Reaktion erneut zu wählen nimmt sie zurück.

**Teilen-Buttons:** `<?= $this->shareButtons() ?>` gibt Twitter, Facebook, LinkedIn, Xing und E-Mail aus (Templates `cm_share_button_*`).

**iCalendar:** `<?= $this->iCalendarUrl() ?>` liefert einen Link auf eine `.ics`-Datei des Eintrags.

### Sitemap und Suche

Sollen Einträge in der Sitemap erscheinen, wähle den Katalog in einem Listen-Modul aus und lege dort eine Detailseite fest. Unveröffentlichte Einträge werden ausgelassen. In der **Backend-Suche** von Contao werden Katalogeinträge mitgefunden; angezeigt werden nur Einträge, die der Backend-Benutzer sehen darf.

### Insert-Tags

| Insert-Tag | Beschreibung | Beispiel |
|---|---|---|
| `{{ACTIVE::param}}` | Wert eines GET-/POST-Parameters bzw. eines Seitenfilters | `{{ACTIVE::category}}` |
| `{{ACTIVE::param::?optionen}}` | Optionen: `default=…`, `csv=1` (Kommaliste als Mehrfachwert), `isDate=1` mit `dateMethod=dayBegin` bzw. `dayEnd` und `dateFormat=…`, `mode=FE` bzw. `mode=BE`, `source=tabelle:spalte` (Alias in IDs umwandeln). Mehrere Optionen mit `[&]` trennen. | `{{ACTIVE::from::?isDate=1[&]dateMethod=dayBegin}}` |
| `{{MASTER::feld}}` | Wert des aktuellen Detail-Eintrags; dritter Teil ist ein Standardwert | `{{MASTER::title::Kein Titel}}` |
| `{{MASTER::origin>feld}}` | Rohwert des Detail-Eintrags | `{{MASTER::origin>startDate}}` |
| `{{MASTER::getParent>feld}}` | Feld des Eltern-Eintrags | `{{MASTER::getParent>title}}` |
| `{{MASTER::getRelated>feld>unterfeld}}` | Kommaliste aus verknüpften Einträgen | `{{MASTER::getRelated>artists>title}}` |
| `{{TIMESTAMP::methode::strtotime}}` | Zeitstempel relativ zu jetzt (`tstamp`, `dayBegin`, `dayEnd`, `monthBegin` …) | `{{TIMESTAMP::dayBegin::+ 1 days}}` |
| `{{ACTIVE_PAGE}}` | IDs der aktuellen Seite und ihrer Unterseiten (serialisiert); `?useParent=1`, `?translate=1`, `?reverse=1` | `{{ACTIVE_PAGE::?useParent=1}}` |
| `{{cm-user::feld}}` | Feld des angemeldeten Mitglieds; sensible Felder (Passwort, 2FA, Sitzung …) sind gesperrt | `{{cm-user::firstname}}` |
| `{{LAST-ADDED-MASTER-VIEW-IDS::tabelle}}` | IDs der zuletzt angesehenen Detail-Einträge | `{{LAST-ADDED-MASTER-VIEW-IDS::cm_products}}` |
| `{{WATCHLIST…}}` | siehe [Merkliste](#merkliste) | |

---

## 5. Frontend-Verwaltung (Frontend-Manager)

### Was die Frontend-Verwaltung leistet

Das Frontend-Modul **Catalog Manager → Frontend-Verwaltung** (Typ `catalog-frontend-manager`, seit 4.1) lässt angemeldete Mitglieder Datensätze **im Frontend** verwalten, ohne Backend-Zugang. Typische Fälle: Veranstalter pflegen ihre Termine, Firmen ihren Branchenbucheintrag, Mitglieder ihr Profil oder ihre Inserate.

| Funktion | Beschreibung |
|---|---|
| Liste | Konfigurierbare Spalten, Sortierung per Klick, Filterfelder, Paginierung |
| Neu anlegen, Bearbeiten | Formular aus dem DCA, mit Paletten, Subpaletten, Validierung und Callbacks wie im Backend |
| Duplizieren, Löschen | Löschen mit Bestätigungsseite |
| Sichtbarkeit | Ein-/Ausblenden über ein Checkbox-Feld (z. B. `published`) |
| Uploads | Einstellungen aus dem Feld, Home-Verzeichnisse je Mitglied |
| Rich Text | TinyMCE wie im Backend |
| Datum/Uhrzeit | Native Picker des Browsers |
| AJAX | Ohne Seiten-Reload, mit vollständigem Betrieb auch ohne JavaScript |
| Barrierefreiheit | Live-Regionen, Fokusführung, Fehlerzusammenfassung, Mindestgrößen der Bedienelemente, `prefers-reduced-motion`, `forced-colors` |

Bestandteile:

| Bereich | Klassen / Dateien |
|---|---|
| Modul | `src/Modules/FrontendManagerModule.php` |
| Backend-Konfiguration | `src/Resources/contao/dca/tl_module.php` (Felder `cmFm*`), `src/DataContainer/FrontendManager.php` (Optionen, Prüfungen und Hinweise beim Speichern) |
| Konfiguration und Berechtigungen | `Library/FrontendManager/ManagerConfig`, `AccessGuard`, `FieldPolicy`, `TableProvider`, `RecordScope`, `RecordService`, `MemberContext` |
| Formular | `Library/DcFormBuilder.php`, `Library/DcFormBuilder/*` (Uploads, Upload-Ziele, Datumsspeicherung), `Widgets/FormDateTime.php` |
| Speichern | `Mcp/DcaRecordManager.php` (gemeinsam mit dem MCP-Server) |
| Events | `src/Event/FrontendManager/*` (`FrontendManagerEvents`, `RecordCreatedEvent`, `RecordUpdatedEvent`, `RecordDeletedEvent`, `RecordVisibilityChangedEvent`) |
| AJAX-Antworten | `Library/FrontendManager/AjaxResponse.php` |
| Templates | `mod_catalog_frontend_manager`, `cm_frontend_manager_view`, `ce_dc_form` |
| Assets | `public/js/frontend-manager.js`, `public/css/frontend-manager.scss` |

Ablauf einer Anfrage:

```text
Browser ──(Link/Formular, ggf. fetch + _cmfm_ajax)──► Seite mit dem Modul
   │                                                   │ Contao: Seiten-/Modulschutz, Login
   │                                                   ▼
   │                                    FrontendManagerModule
   │                                     ├─ ManagerConfig (Modul-Einstellungen)
   │                                     ├─ AccessGuard   (Mitglied, Tabelle, Felder, Operator)
   │                                     ├─ RecordScope   (Besitzerfeld + Filter → erlaubte Datensätze)
   │                                     ├─ DcFormBuilder (Formular, Validierung, Uploads)
   │                                     └─ RecordService → DcaRecordManager (Callbacks, Speichern)
   ◄──────────── HTML (ohne JS, Post/Redirect/Get) oder JSON (AjaxResponse) ─────┘
```

### Einrichtung Schritt für Schritt

**Voraussetzungen:** ein Katalog mit Datentabelle (z. B. `cm_events`), funktionierender Mitglieder-Login (Login-Modul, Mitglieder) und eine Seite, die nur angemeldete Mitglieder sehen.

1. **Datenbank aktualisieren** (nach Installation oder Update auf 4.1):

   ```bash
   php vendor/bin/contao-console contao:migrate
   ```

2. **Besitzerfeld im Katalog anlegen** (empfohlen): ein Feld für die Mitglieds-ID, z. B. Feldname `member` mit Rolle `member`. Neue Datensätze erhalten automatisch die ID des angemeldeten Mitglieds.
3. **Modul anlegen:** Layout → Themes → Module → Neues Modul, Typ **Frontend-Verwaltung**.
4. **Tabelle wählen:** z. B. `cm_events`.
5. **Felder festlegen:** Spalten der Liste, bearbeitbare Felder, ggf. Nur-Lese-Felder und Filterfelder.
6. **Operatoren** aktivieren: Neu anlegen, Bearbeiten, Löschen …
7. **Datensatzbeschränkung:** Besitzerfeld `member` wählen und/oder einen Filter setzen.
8. **Speichern und Hinweise lesen.** Beim Speichern prüft das Backend die Konfiguration und meldet zum Beispiel:
   - Felder, die zugleich bearbeitbar und Nur-Lese sind (Nur-Lese hat Vorrang),
   - dass kein bearbeitbares Feld übrig ist,
   - das tatsächliche Upload-Ziel jedes Dateifeldes.
9. **Modul einbinden:** in einen Artikel einer geschützten Seite (Seiteneinstellung „Seite schützen“) oder mit Modulschutz.
10. **Testen:** Als Mitglied anmelden, die Seite öffnen, einen Eintrag anlegen und bearbeiten. Danach prüfen, dass ein zweites Mitglied ihn nicht sieht.

### Modul-Einstellungen im Detail

| Einstellung (Feld) | Bedeutung |
|---|---|
| **Tabelle** (`cmTable`) | Catalog-Tabellen aus `tl_catalog`, gruppiert nach „Catalog-Manager-Tabellen“ und „Freigegebene Contao-Tabellen“. |
| **Spalten der Listenansicht** (`cmFmListFields`) | Felder und Reihenfolge der Spalten. Die erste Spalte dient in der Liste als Titel des Datensatzes. |
| **Bearbeitbare Felder** (`cmFmFormFields`) | Nur diese Felder werden aus dem Formular übernommen. Reihenfolge und Gruppen folgen der DCA-Palette. |
| **Nur-Lese-Felder** (`cmFmReadonlyFields`) | Werden angezeigt, aber nie gespeichert. Haben Vorrang vor „bearbeitbar“. |
| **Filterfelder** (`cmFmFilterFields`) | Felder, nach denen Mitglieder die Liste filtern dürfen (Eingabetypen text, textarea, select, radio, checkbox). |
| **Sortierung erlauben** (`cmFmSortable`) | Spalten per Klick sortierbar. |
| **Sortierung, Limit** (`cmOrder`, `cmLimit`) | Standard-Reihenfolge und Einträge pro Seite. |
| **Erlaubte Operatoren** (`cmFmOperations`) | `create`, `edit`, `copy`, `delete`, `toggle`. Bei jeder Anfrage serverseitig geprüft. |
| **Sichtbarkeitsfeld** (`cmFmToggleField`) | Checkbox-Feld für „Sichtbarkeit umschalten“, z. B. `published`. |
| **Umgekehrte Logik** (`cmFmToggleReverse`) | Aktiviert bedeutet unsichtbar, z. B. für ein Feld `invisible`. |
| **Besitzerfeld** (`cmFmOwnerField`) | Feld mit der Mitglieds-ID. Neue Datensätze erhalten die ID; das Feld ist nie bearbeitbar. |
| **Filter** (`cmFilter`) | Zusätzliche Einschränkung mit der Syntax der Listen ([Filter](#filter)); Insert-Tags wie `{{user::id}}` sind erlaubt. |
| **Interne Werte** (`cmFmInternalValues`) | Schlüssel-Wert-Paare, die beim Anlegen gesetzt werden (Insert-Tags erlaubt), z. B. `pid` = `3`. Diese Felder sind nicht bearbeitbar. |
| **Ein Home-Verzeichnis festlegen** (`cmFmUseHomeDir`) | Uploads im Home-Verzeichnis des Mitglieds speichern, siehe unten. |
| **Individuelles Template** (`customTpl`) | Eigene Variante von `mod_catalog_frontend_manager`. |

**Contao-Tabellen freigeben** (in `contao/config/config.php` des Projekts):

```php
$GLOBALS['CM_FRONTEND_MANAGER']['tables'][] = 'tl_news';
```

Tabellen mit Zugangsdaten oder Systemkonfiguration sind **nie** wählbar, auch nicht nach einer Freigabe. Gesperrt sind `tl_member`, `tl_user`, `tl_page`, `tl_module`, `tl_content`, `tl_article`, `tl_files`, `tl_form`, `tl_layout`, `tl_settings`, `tl_version`, `tl_watchlist` und weitere sowie alle Tabellen mit den Präfixen `tl_catalog`, `tl_user_`, `tl_member_`, `tl_search_` und `tl_opt_in`.

**Nicht angeboten** werden Systemfelder (`id`, `pid`, `ptable`, `tstamp`, `sorting`, `lid`, `language`), Geheimfelder (`password`, `secret`, `backupCodes`, `session`, `activation`) und Felder ohne Frontend-Widget.

### Berechtigungen und Sicherheit

Jede Aktion wird **serverseitig** auf mehreren Ebenen geprüft. Ausgeblendete Schaltflächen sind nur eine Bedienhilfe.

| Ebene | Prüfung |
|---|---|
| 1. Anmeldung | Ein angemeldetes Mitglied ist nötig. Eine Gruppenzuordnung ist **nicht** erforderlich. Seiten-, Artikel- und Modulschutz von Contao gelten zusätzlich. |
| 2. Tabelle | Nur Catalog-Tabellen und freigegebene Contao-Tabellen; Sperrliste siehe oben. |
| 3. Felder | Nur konfigurierte Felder werden gelesen bzw. gespeichert. Nur-Lese-, Besitzer- und interne Felder sind nie beschreibbar. |
| 4. Operator | Nur aktivierte Operatoren. |
| 5. Datensatz | Der Datensatz muss in der erlaubten Menge aus Besitzerfeld und Filter liegen. Fremde und nicht vorhandene IDs sind nicht unterscheidbar (immer 404). Ohne Besitzerfeld und ohne Filter ist **kein** Datensatz erreichbar. |
| CSRF | Alle schreibenden Anfragen brauchen ein gültiges Request-Token, auch per AJAX. |

Gespeichert wird über die DCA-Callbacks wie im Backend. Technische Fehlerdetails stehen nur im Contao-Log (`var/logs/`), nie in der Antwort an den Browser.

> **Hinweis:** Ohne Gruppenbindung kann jedes registrierte Mitglied die Verwaltung öffnen; es sieht aber nur seine eigenen Datensätze. Willst du die Verwaltung auf bestimmte Gruppen beschränken, schütze Seite oder Modul in Contao für diese Gruppen.

### Formularfelder

Das Formular entsteht aus dem DCA der Tabelle (`DcFormBuilder`). Die DCA-Eingabetypen werden auf Contao-Frontend-Formularfelder (`TL_FFL`) abgebildet:

| DCA `inputType` | Frontend-Feld |
|---|---|
| `text` | `text` (mit `rgxp` `date`/`time`/`datim`: `FormDateTime`) |
| `textarea` | `textarea` (mit `rte=tiny…`: TinyMCE) |
| `select`, `radio`, `checkbox` | gleichnamiges Feld |
| `fileTree` | `upload` |

Weitere Zuordnungen in `contao/config/config.php`:

```php
// DCA-Eingabetyp "colorpicker" als einfaches Textfeld im Frontend
$GLOBALS['CM_FRONTEND_MANAGER']['widgets']['colorpicker'] = 'text';
```

Übernommen werden Pflichtfeld, `rgxp`, `maxlength`, `unique`, Optionen (auch `options_callback` und Relationen), Paletten und Subpaletten. Ändert ein Mitglied ein Selektorfeld (z. B. den Typ), wird das Formular mit der passenden Palette neu geladen. Bei Fehlern bleiben die Eingaben erhalten.

HTML ist nur in Rich-Text-Feldern erlaubt und wird mit Contaos Filter (`allowedTags`) bereinigt. In allen anderen Feldern wird `allowHtml` aus Sicherheitsgründen ignoriert.

### Datei-Uploads und Home-Verzeichnisse

Die Upload-Einstellungen kommen aus dem **Feld**, also aus dem Catalog-Manager-Feld „Bild(er) / Datei(en)“ bzw. aus DCA-`eval`. Globale Werte dienen nur als Fallback und begrenzen zugleich nach oben:

| Einstellung | Feld (DCA `eval`) | Fallback | Obergrenze |
|---|---|---|---|
| Zielordner | `uploadFolder` (UUID oder Pfad) | `catalog_manager.frontend_manager.upload_folder` | muss in `%contao.upload_path%` liegen |
| Dateitypen | `extensions` | localconfig `uploadTypes` | immer nur Typen aus `uploadTypes`; ausführbare Typen nie |
| Max. Dateigröße | `maxFileSize` (KB) | localconfig `maxFileSize` | PHP `upload_max_filesize` / `post_max_size` |
| Max. Bildmaße | `imageWidth` / `imageHeight` | localconfig `imageWidth` / `imageHeight` | |

Im Catalog-Manager-Feld gibt es keine Einstellung für die maximale Dateigröße. Für Catalog-Felder gilt deshalb `maxFileSize` aus den Einstellungen, für eigene DCA-Felder `eval.maxFileSize`.

**Upload-Ziel, in dieser Reihenfolge:**

1. **Home-Verzeichnis:** wenn im Modul „Ein Home-Verzeichnis festlegen“ oder im Feld „Home-Verzeichnis verwenden“ aktiviert ist.
2. **Ordner des Feldes** (`uploadFolder`).
3. **Globaler Standardordner** (`catalog_manager.frontend_manager.upload_folder`).

**Home-Verzeichnisse** folgen der Contao-Konvention. Hat das Mitglied bereits ein Home-Verzeichnis (`tl_member.assignDir`/`homeDir`), wird es verwendet. Sonst wird `<home_folder>/user_<Mitglieds-ID>` angelegt und dem Mitglied zugewiesen. Der Ordnername enthält keine Benutzereingaben, jedes Mitglied hat einen eigenen Ordner.

**Sicherheit beim Upload:**

- Ziele außerhalb von `%contao.upload_path%`, mit `..`, mit versteckten Ordnern oder mit Symlinks nach außen werden abgelehnt.
- Ausführbare Endungen werden abgelehnt, auch als Zwischenendung (`bild.php.png`).
- Dateien mit Bildendung müssen echte Bilder sein; SVGs bereinigt Contao.
- Vorhandene Dateien werden nie überschrieben: Contao vergibt bei Namensgleichheit einen neuen Namen.
- Ein Formular kann nur Dateien zuordnen, die in derselben Sitzung hochgeladen wurden; fremde Datei-UUIDs werden ignoriert.
- Ordner werden erst beim tatsächlichen Upload angelegt und nur, wenn `create_folders` aktiv ist.

Neu angelegte Ordner sind wie in Contao üblich **geschützt**. Sollen Uploads öffentlich verlinkt werden (z. B. Bilder in der Liste), gib den Upload-Ordner in der Dateiverwaltung frei. Home-Verzeichnisse sollten geschützt bleiben.

Erlaubte Typen und Maximalgröße zeigt das Formular als Hilfetext am Feld an (per `aria-describedby` verknüpft).

### Rich Text (TinyMCE)

Ist im Feld `eval.rte` ein TinyMCE-Editor gesetzt, erscheint der Editor auch im Frontend. Bei Catalog-Feldern ist das die Option „Rich Text Editor“ mit Typ `tinyMCE`.

- Gerendert wird das **Backend-Template** `be_tinyMCE`. Eigene Anpassungen in `templates/be_tinyMCE.html5` gelten damit für Backend und Frontend.
- `tinymce.min.js` wird automatisch geladen, auch beim AJAX-Nachladen.
- `frontend-manager.js` startet die Editoren, entfernt sie vor dem Ersetzen einer Ansicht und schreibt die Inhalte vor dem Absenden in die Textareas.
- Bildpfade werden wie im Backend gespeichert (`{{file::uuid}}`).
- Datei- und Link-Auswahl aus Dateiverwaltung bzw. Seitenbaum gibt es nicht, weil sie einen Backend-Login brauchen.
- Lädt TinyMCE nicht, bleibt das normale Textarea nutzbar.

### Datum und Uhrzeit

Textfelder mit `eval.rgxp` werden als native Browser-Eingaben dargestellt:

| `rgxp` | Eingabe | Browser-Wert |
|---|---|---|
| `date` | `type="date"` | `2026-12-24` |
| `time` | `type="time"` | `19:30` |
| `datim` | `type="datetime-local"` | `2026-12-24T19:30` |

Laden, Prüfen und Speichern laufen wie im Backend:

- Contao formatiert und prüft mit `dateFormat`, `timeFormat` bzw. `datimFormat` (`Date::getFormatFromRgxp()`).
- Gespeichert wird ein Unix-Timestamp in der Zeitzone der Installation. Backend und Frontend speichern für dieselbe Eingabe denselben Wert.
- Ungültige Werte (31.02., 25:00) werden serverseitig abgelehnt.
- Werte im eingestellten Format (`24.12.2026`) werden ebenfalls angenommen, z. B. aus Browsern ohne Picker.

Abweichende DCA-Konfigurationen haben Vorrang:

| DCA | Speicherung |
|---|---|
| Standard (z. B. `int(11)`, alle Catalog-Datumsfelder) | Timestamp |
| `load_callback` / `save_callback` vorhanden | Callbacks erhalten wie im Backend den Timestamp und wandeln selbst um |
| `sql` vom Typ `date`, `datetime` oder `time` | SQL-Format `Y-m-d`, `Y-m-d H:i:s` bzw. `H:i:s` |

### JavaScript und AJAX

**Ohne JavaScript** funktioniert alles mit normalen Links und Formularen (Post/Redirect/Get).

**Mit JavaScript** (`frontend-manager.js`, keine Abhängigkeiten) laufen alle Aktionen per `fetch()` ohne Seiten-Reload, auch bei Fehlern:

- Anfragen gehen an **dieselbe Seiten-URL** mit dem Parameter `_cmfm_ajax=<Modul-ID>` und dem Header `X-Requested-With`. Sie durchlaufen also Seitenschutz, Berechtigungen und Token-Prüfung wie ohne JavaScript.
- Zentrale Fehlerbehandlung für Validierung, Sitzung, Berechtigung, CSRF (ein automatischer Wiederholungsversuch mit neuem Token), Datenbank, Server, ungültige Antworten, Netzwerk und Zeitüberschreitung (30 s).
- Lade- und Sperrzustände (`aria-busy`, `aria-disabled`), keine doppelten Übermittlungen.
- Browser-History: Zurück und Vor funktionieren, URLs sind teilbar.
- Fokus nach jedem Wechsel auf dem auslösenden Element, der Fehlerzusammenfassung oder der Überschrift.

**URL-Parameter** (auch ohne JavaScript):

| Parameter | Bedeutung |
|---|---|
| `cmfm_act=create` / `edit` / `delete` | Formular bzw. Löschbestätigung |
| `cmfm_id` | Datensatz-ID |
| `cmfm_f[feld]` | Filterwerte der Liste |
| `cmfm_sort`, `cmfm_dir` | Sortierung (`asc`/`desc`) |

**Antwortformat (JSON):**

```json
{
  "status": "success | validation_error | error",
  "code": "ok | validation_failed | not_authenticated | forbidden | not_found | csrf_invalid | bad_request | database_error | server_error",
  "message": null,
  "messages": [{"type": "confirm", "text": "Der Eintrag wurde gespeichert."}],
  "view": "list | form | delete",
  "html": "…",
  "url": "/meine-eintraege?cmfm_act=edit&cmfm_id=12",
  "errors": {"title": ["Bitte füllen Sie das Feld aus!"]},
  "record": {"id": 12},
  "assets": {"css": [], "js": [], "html": []},
  "token": "…"
}
```

HTTP-Status: 200, 400, 401 (Sitzung abgelaufen), 403 (keine Berechtigung bzw. ungültiges Token), 404 (Datensatz), 422 (Validierung), 500 (Datenbank bzw. Server). Antworten werden nicht gecacht (`Cache-Control: no-store, private`).

**JavaScript-Events** (am Modul-Container, bubbeln bis `document`):

| Event | Zeitpunkt |
|---|---|
| `cm-fm:updated` | Eine Ansicht wurde nachgeladen und eingefügt |
| `cm-fm:editor-loaded` | Ein TinyMCE-Editor ist bereit (`event.detail.editor`, `event.detail.element`) |
| `cm-fm:editors-ready` | Alle Editoren der Ansicht sind initialisiert |

```js
// Eigene Widgets nach jedem Nachladen initialisieren
document.addEventListener('cm-fm:updated', (event) => initMyWidget(event.target));
```

Links mit `data-cm-fm-native` (sowie `download`-Links und Links mit `target`) werden nicht abgefangen.

### Templates, Styling und Assets

| Template | Inhalt |
|---|---|
| `mod_catalog_frontend_manager` | Rahmen, Meldungsbereiche (`role="status"` / `role="alert"`), Konfiguration für das Skript |
| `cm_frontend_manager_view` | Liste mit Filter, Sortierung, Paginierung und Aktionen; Löschbestätigung |
| `ce_dc_form` | Formular mit Fieldsets, Fehlerzusammenfassung und Aktionen |

Eigene Varianten legst du wie üblich in `/templates` ab. Für das Modul-Template wählst du sie im Feld „Individuelles Template“ aus. Die `data-cm-fm-*`-Attribute brauchen das Skript, lass sie bestehen.

**Farben** passt du über Custom Properties im Theme-CSS an:

```css
.cm-fm {
    --cm-fm-accent: #8a1538;           /* Primärfarbe (Buttons, Links) */
    --cm-fm-accent-contrast: #fff;     /* Text auf der Primärfarbe */
    --cm-fm-danger: #b00020;           /* Löschen, Fehler */
    --cm-fm-focus: #1a73e8;            /* Fokusrahmen */
    --cm-fm-border: #c4c4c4;
    --cm-fm-muted: #5f6368;
    --cm-fm-error-bg: #fdecea;
    --cm-fm-confirm-bg: #e6f4ea;
}
```

**Assets und Build:** Es gibt keinen separaten Build-Schritt und keine npm-Abhängigkeiten. Das Modul bindet auf Seiten, auf denen es vorkommt, zwei Dateien über Contaos `Combiner` ein:

- `bundles/alnvcontaocatalogmanager/css/frontend-manager.scss`, von Contao beim ersten Aufruf zu CSS kompiliert (Cache in `assets/css/`),
- `bundles/alnvcontaocatalogmanager/js/frontend-manager.js`, mit `defer` am Seitenende.

Nach Änderungen an diesen Dateien bzw. nach einem Update:

```bash
php vendor/bin/contao-console assets:install public
php vendor/bin/contao-console cache:clear --env=prod
```

Im Contao-Backend unter „Systemwartung“ kannst du zusätzlich den Skript-Cache leeren. Beim Entwickeln am Bundle siehe [Tests](#tests).

### Events für Entwickler:innen

Nach jeder erfolgreichen Aktion löst die Frontend-Verwaltung ein **Symfony-Event** aus. Daran hängst du eigene Logik, z. B. E-Mail-Benachrichtigungen, Schnittstellen zu externen Systemen (CRM, Ticketsystem, Webhooks), Protokollierung oder Cache-Invalidierung.

| Event-Name (Konstante in `FrontendManagerEvents`) | Event-Klasse | Ausgelöst bei |
|---|---|---|
| `catalog_manager.frontend_manager.record_created` (`RECORD_CREATED`) | `RecordCreatedEvent` | Neu anlegen und Duplizieren |
| `catalog_manager.frontend_manager.record_updated` (`RECORD_UPDATED`) | `RecordUpdatedEvent` | Speichern im Formular (Bearbeiten) |
| `catalog_manager.frontend_manager.record_deleted` (`RECORD_DELETED`) | `RecordDeletedEvent` | Löschen |
| `catalog_manager.frontend_manager.record_visibility_changed` (`RECORD_VISIBILITY_CHANGED`) | `RecordVisibilityChangedEvent` | Sichtbarkeit umschalten |

Namespace: `Alnv\ContaoCatalogManagerBundle\Event\FrontendManager`.

**Wann genau:** Die Events kommen **nach** dem Speichern, wenn alle Berechtigungsprüfungen und DCA-Callbacks gelaufen sind. Abgelehnte oder fehlgeschlagene Aktionen lösen kein Event aus. Mit und ohne JavaScript gibt es dieselben Events.

**Daten der Events:**

| Methode | Events | Inhalt |
|---|---|---|
| `getTable()` | alle | Tabelle, z. B. `cm_events` |
| `getId()` | alle | ID des Datensatzes |
| `getMember()` | alle | Auslösendes Mitglied (`->id`, `->username`) |
| `getModuleId()` | alle | ID des Frontend-Moduls; unterscheidet mehrere Verwaltungen |
| `getRecord()` | Created, Deleted | Neuer Datensatz bzw. Datensatz vor dem Löschen |
| `isCopy()`, `getSourceId()` | Created | Durch Duplizieren entstanden; ID des Originals |
| `getOldRecord()`, `getNewRecord()` | Updated, VisibilityChanged | Datensatz vor und nach dem Speichern |
| `getChanges()` | Updated | `['feld' => ['old' => …, 'new' => …]]`, ohne `tstamp`, inkl. Änderungen durch Callbacks (z. B. ein neu erzeugter Alias) |
| `getChangedFields()`, `isChanged('feld')`, `hasChanges()` | Updated | Geänderte Felder |
| `getSubmittedFields()` | Updated | Vom Mitglied übermittelte Felder |
| `isVisible()`, `getField()` | VisibilityChanged | Neue Sichtbarkeit (berücksichtigt „umgekehrte Logik“), Sichtbarkeitsfeld |

`RECORD_UPDATED` kommt bei **jedem** erfolgreichen Speichern, auch wenn sich nichts geändert hat. Mit `hasChanges()` filterst du solche Fälle heraus.

Datensätze liegen im **Datenbankformat** vor: Timestamps, serialisierte Arrays, Datei-UUIDs binär. Umwandeln mit `StringUtil::deserialize()`, `StringUtil::binToUuid()` bzw. `Date::parse()`.

**Fehler in Listenern** brechen die Aktion des Mitglieds nicht ab. Der Datensatz ist bereits gespeichert; der Fehler landet im Contao-Log (`var/logs/`), und das Mitglied sieht die normale Erfolgsmeldung. Wenn dein Listener zuverlässig zustellen muss, sorge selbst dafür (z. B. Symfony Messenger oder eine eigene Warteschlange).

**Listener registrieren** (Contao 5, Projektordner `src/`):

```yaml
# config/services.yaml (falls noch nicht vorhanden)
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\:
    resource: '../src/'
```

**Beispiel 1: E-Mail an die Redaktion bei neuen Einträgen**

```php
<?php
// src/EventListener/NotifyEditorsListener.php

namespace App\EventListener;

use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\FrontendManagerEvents;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsEventListener(FrontendManagerEvents::RECORD_CREATED)]
final class NotifyEditorsListener
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function __invoke(RecordCreatedEvent $event): void
    {
        if ('cm_events' !== $event->getTable() || $event->isCopy()) {
            return;
        }

        $record = $event->getRecord();

        $this->mailer->send((new Email())
            ->from('website@example.org')
            ->to('redaktion@example.org')
            ->subject('Neue Veranstaltung: ' . $record['name'])
            ->text(sprintf("Mitglied %s hat die Veranstaltung \"%s\" (ID %d) angelegt.",
                $event->getMember()->username, $record['name'], $event->getId())));
    }
}
```

**Beispiel 2: Änderungen an ein externes System melden (alte und neue Werte)**

```php
<?php
// src/EventListener/SyncChangesListener.php

namespace App\EventListener;

use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\FrontendManagerEvents;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsEventListener(FrontendManagerEvents::RECORD_UPDATED)]
final class SyncChangesListener
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function __invoke(RecordUpdatedEvent $event): void
    {
        // Nur echte Änderungen an relevanten Feldern melden
        if (!$event->isChanged('name') && !$event->isChanged('location')) {
            return;
        }

        $this->httpClient->request('POST', 'https://crm.example.org/api/events/' . $event->getId(), [
            'json' => [
                'table' => $event->getTable(),
                'member' => $event->getMember()->id,
                'changes' => array_intersect_key($event->getChanges(), array_flip(['name', 'location'])),
            ],
            'timeout' => 5,
        ]);
    }
}
```

**Beispiel 3: Mehrere Events in einer Klasse (Protokoll)**

```php
<?php
// src/EventListener/FrontendManagerAuditListener.php

namespace App\EventListener;

use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\FrontendManagerEvents;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordDeletedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordVisibilityChangedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class FrontendManagerAuditListener
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.contao.general')]
        private readonly LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(FrontendManagerEvents::RECORD_DELETED)]
    public function onDeleted(RecordDeletedEvent $event): void
    {
        $this->logger->info(sprintf('Mitglied %d hat %s.%d gelöscht', $event->getMember()->id, $event->getTable(), $event->getId()));
    }

    #[AsEventListener(FrontendManagerEvents::RECORD_VISIBILITY_CHANGED)]
    public function onVisibilityChanged(RecordVisibilityChangedEvent $event): void
    {
        $this->logger->info(sprintf('%s.%d ist jetzt %s', $event->getTable(), $event->getId(), $event->isVisible() ? 'sichtbar' : 'ausgeblendet'));
    }
}
```

Danach `php vendor/bin/contao-console cache:clear`. Ob der Listener registriert ist, zeigt `php vendor/bin/contao-console debug:event-dispatcher catalog_manager.frontend_manager.record_created`.

> **Notification Center:** Eine fertige Anbindung an das Notification Center (terminal42) gibt es **nicht**. Über die Events lässt es sich aber in einem eigenen Listener ansteuern. Bei Interesse an einer fertigen Integration schreib bitte an den Support: <support@catalog-manager.org>.

### Erweitern

| Ziel | Weg |
|---|---|
| Weitere Contao-Tabelle verwalten | `$GLOBALS['CM_FRONTEND_MANAGER']['tables'][] = 'tl_…';` |
| Weiteren DCA-Eingabetyp unterstützen | `$GLOBALS['CM_FRONTEND_MANAGER']['widgets']['<inputType>'] = '<TL_FFL-Schlüssel>';` |
| Werte beim Speichern verändern | `save_callback` / `onsubmit_callback` im DCA; laufen auch im Frontend (die Events kommen danach und sind nur zum Reagieren gedacht) |
| Eigene Darstellung | Templates überschreiben, Custom Properties |
| Eigene Skripte | Events `cm-fm:updated`, `cm-fm:editor-loaded` |
| Benachrichtigungen, externe APIs | PHP-Events `FrontendManagerEvents::*`, siehe [Events für Entwickler:innen](#events-für-entwicklerinnen) |
| TinyMCE-Konfiguration | `templates/be_tinyMCE.html5` (wirkt auch im Backend) |
| Upload-Standardordner | Parameter `catalog_manager.frontend_manager.*` |

### Beispiele für die Frontend-Verwaltung

**Beispiel 1: Veranstalter pflegen eigene Termine**

- *Ziel:* Jedes Mitglied sieht und bearbeitet nur seine eigenen Veranstaltungen.
- *Katalog `cm_events`:* Feld `member` (Rolle `member`), `title`, `startDate` (Rolle `startDate`), `teaser` mit Rich Text, `image`, `published`.
- *Modul:*

  | Einstellung | Wert |
  |---|---|
  | Tabelle | `cm_events` |
  | Spalten | `title`, `startDate` |
  | Bearbeitbare Felder | `title`, `startDate`, `teaser`, `image` |
  | Operatoren | Neu anlegen, Bearbeiten, Löschen, Sichtbarkeit |
  | Sichtbarkeitsfeld | `published` |
  | Besitzerfeld | `member` |

- *Ergebnis:* Ein neuer Termin erhält automatisch die ID des Mitglieds. Datum per Datepicker, Teaser per TinyMCE. Die Überschrift beim Bearbeiten lautet „„Konzert im Park“ bearbeiten“ (Rolle `title`). Fremde Termine liefern 404.

**Beispiel 2: Einträge einem festen Elterndatensatz zuordnen**

- *Ziel:* In einem Kind-Katalog (z. B. Aufführungen einer Spielzeit) sollen neue Einträge immer zur Spielzeit 3 gehören.
- *Modul:* Interne Werte `pid` = `3`; Filter Experte: Spalten `pid=?`, Werte `3`.
- *Ergebnis:* Mitglieder sehen und erzeugen nur Einträge dieser Spielzeit. `pid` ist nicht bearbeitbar.

**Beispiel 3: Uploads in persönliche Ordner**

- *Ziel:* Jedes Mitglied lädt Bilder in einen eigenen, geschützten Ordner.
- *Konfiguration:* im Modul „Ein Home-Verzeichnis festlegen“ aktivieren; optional den Basisordner festlegen:

  ```yaml
  # config/parameters.yaml
  parameters:
    catalog_manager.frontend_manager.home_folder: 'files/members'
  ```

- *Ergebnis:* Beim ersten Upload von Mitglied 7 entsteht `files/members/user_7`, der Ordner wird als Home-Verzeichnis in `tl_member` eingetragen. Spätere Uploads landen dort. Beim Speichern des Moduls zeigt das Backend das Ziel an.

**Beispiel 4: Frontend-Verwaltung für Contao-News**

- *Ziel:* Redaktionsmitglieder pflegen News im Frontend.
- *Konfiguration:*

  ```php
  // contao/config/config.php
  $GLOBALS['CM_FRONTEND_MANAGER']['tables'][] = 'tl_news';
  ```

  Danach `cache:clear`; im Modul `tl_news` wählen, Interne Werte `pid` = ID des News-Archivs, Filter `pid=?` / ID des Archivs. Weil `tl_news` kein Besitzerfeld hat, sehen alle berechtigten Mitglieder alle News dieses Archivs. Schütze deshalb die Seite für die Redaktionsgruppe.

**Beispiel 5: Eigenes Widget nach dem Nachladen initialisieren**

```js
document.addEventListener('cm-fm:updated', (event) => {
    event.target.querySelectorAll('select[data-choices]').forEach((select) => new Choices(select));
});
```

---

## 6. MCP-Server (Model Context Protocol)

### Was ist MCP?

Das **Model Context Protocol** ist ein offener Standard, über den KI-Assistenten (MCP-Clients wie Claude Desktop oder Claude Code) Werkzeuge externer Programme (MCP-Server) aufrufen. Der Server beschreibt seine Werkzeuge mit Namen, Beschreibung und JSON-Schema; der Client entscheidet anhand der Anfrage des Nutzers, welches Werkzeug er mit welchen Argumenten aufruft. Kommuniziert wird per JSON-RPC 2.0.

Im Catalog Manager heißt das: Du beschreibst in natürlicher Sprache, was du brauchst („Leg einen Katalog für Stellenangebote an“), und der Assistent erledigt es über die Werkzeuge des Servers: Katalog anlegen, Felder, Optionen, Module, Templates.

### So ist MCP integriert

- **Konsolenbefehl `catalog:mcp`** (`src/Mcp/McpCommand.php`) auf Basis von `php-mcp/server`. Er läuft als Prozess, der über **stdin/stdout** spricht (Transport stdio). Es gibt keinen offenen Port und keinen Web-Endpunkt.
- **Optional:** Ohne installiertes `php-mcp/server` wird der Befehl nicht registriert (`DependencyInjection/AlnvContaoCatalogManagerExtension.php`). In Produktion entsteht so keine unnötige Angriffsfläche.
- **Werkzeuge** (`Mcp/Tools.php`) → **`DcaRecordManager`** für Datensätze und **`TemplateManager`** für Templates.
- **Gleiche Logik wie im Backend:** Der `DcaRecordManager` lädt das DCA und prüft Paletten, Pflichtfelder, Optionen und `rgxp`. Er führt `save_callback`, `onsubmit_callback`, `oncreate_callback` und `ondelete_callback` in derselben Reihenfolge wie das Backend aus. Legt der Assistent einen Katalog an, entsteht die Datentabelle; ein neues Feld bekommt seine Spalte, beim Umbenennen wird die Spalte umbenannt. Dieselbe Klasse speichert auch die Daten der Frontend-Verwaltung.
- **Anweisungen an die KI:** Der Server liefert eine Arbeitsanleitung mit, nämlich zuerst `catalog_overview`, dann `config_describe`, erst danach schreiben, und beim Löschen immer zuerst die Vorschau.

### Einrichtung

1. **Paket installieren** (im Contao-Projekt):

   ```bash
   composer require php-mcp/server:^3.3
   php vendor/bin/contao-console cache:clear --env=prod
   ```

2. **Prüfen:**

   ```bash
   php vendor/bin/contao-console list catalog
   php vendor/bin/contao-console catalog:mcp --help
   ```

3. **Optionen des Befehls:**

   | Option | Standard | Bedeutung |
   |---|---|---|
   | `--read-only` | aus | Nur lesende Werkzeuge anbieten (`catalog_overview`, `config_describe`, `record_list`, `record_get`, `template_list`, `template_read`, `template_context`) |
   | `--language=<code>` | `de` | Sprache der Feldbezeichnungen und Optionen |
   | `--env=prod` | | Contao im Produktionsmodus starten (empfohlen) |

   Der Befehl wartet auf JSON-RPC-Nachrichten. Von Hand gestartet scheint er zu „hängen“; das ist korrekt, beende ihn mit Strg+C. PHP-Meldungen gehen nach stderr, damit stdout dem Protokoll vorbehalten bleibt.

### Clients verbinden

**Claude Desktop, Server per SSH:** Einstellungen → Entwickler → Konfiguration bearbeiten (`claude_desktop_config.json`):

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

- Anmeldung per **SSH-Schlüssel ohne Passwortabfrage**, denn der Client kann kein Passwort eintippen.
- Die Login-Shell auf dem Server darf **nichts auf stdout ausgeben** (z. B. kein `echo` in `.bashrc`); das würde das Protokoll stören.
- Auf dem Server muss `php` im `PATH` die richtige Version sein, sonst den vollen Pfad angeben (z. B. `/usr/bin/php8.3`).

**Claude Desktop, lokale Installation** (z. B. eine Entwicklungsumgebung auf demselben Rechner):

```json
{
  "mcpServers": {
    "contao-catalog-local": {
      "command": "php",
      "args": ["/pfad/zum/contao/vendor/bin/contao-console", "catalog:mcp", "--env=prod", "--read-only"]
    }
  }
}
```

**Claude Code** (Terminal):

```bash
# per SSH
claude mcp add contao-catalog -- ssh -T user@dein-server.de "cd /pfad/zum/contao && php vendor/bin/contao-console catalog:mcp --env=prod"

# lokal, nur lesend
claude mcp add contao-catalog-ro -- php /pfad/zum/contao/vendor/bin/contao-console catalog:mcp --env=prod --read-only
```

Andere MCP-Clients, die den Transport stdio unterstützen, verbindest du nach demselben Muster: Befehl `php` bzw. `ssh` mit den oben gezeigten Argumenten.

### Die Werkzeuge

| Werkzeug | Art | Zweck |
|---|---|---|
| `catalog_overview` | lesen | Alle Kataloge mit Tabelle, Feldern, Anzahl Einträge, dazu Listen-, Detail- und Karten-Module, Inhaltselemente und die beschreibbaren Tabellen |
| `config_describe` | lesen | Konfigurationsmöglichkeiten einer Tabelle: aktive Felder mit Bezeichnung, Eingabetyp, Pflichtangabe und erlaubten Optionen. Mit `context` eine andere Palette wählen, z. B. `{"type":"select"}`; mit `id` einen bestehenden Datensatz als Grundlage verwenden |
| `record_list` | lesen | Datensätze lesen: `where` (Gleichheit, z. B. `{"pid": 3}`), `orderBy`, `limit` (max. 500), `offset`, `columns` |
| `record_get` | lesen | Einen Datensatz lesen |
| `record_create` | schreiben | Anlegen; `pid` für Felder, Optionen, Paletten; `pid` + `ptable` für Inhaltselemente. Mehrfachwerte als Array, Checkboxen als `true`/`false`, Dateien als UUID oder Pfad |
| `record_update` | schreiben | Nur die angegebenen Felder ändern |
| `record_delete` | löschen | Mit Unterdatensätzen löschen (bei Katalogen auch die Datentabelle). Ohne `confirm=true` nur eine Vorschau; vorher JSON-Sicherung nach `var/catalog-mcp/backups/` |
| `template_list` | lesen | Eigene Templates in `/templates` und Standard-Templates des Bundles |
| `template_read` | lesen | Ein Template lesen (eigenes oder Standard, z. B. `cm_listing_default`) |
| `template_context` | lesen | Felder, Rollen und Template-Variablen eines Katalogs, damit Templates passen |
| `template_write` | schreiben | `.html5`-Template nach `/templates` schreiben, nach PHP-Syntaxprüfung; ersetzte Fassung nach `var/catalog-mcp/templates/` |
| `template_delete` | löschen | Eigenes Template entfernen (mit Sicherung); ohne `confirm=true` passiert nichts |

**Freigegebene Tabellen:**

| Tabelle | Inhalt | Hinweis |
|---|---|---|
| `tl_catalog` | Kataloge | |
| `tl_catalog_field` | Felder | `pid` = Katalog-ID |
| `tl_catalog_option` | Optionen | `pid` = Feld-ID |
| `tl_catalog_palette` | Paletten | `pid` = Katalog-ID |
| `tl_module` | Frontend-Module | nur Typen `listing-table`, `master`, `listing-map` |
| `tl_content` | Inhaltselemente | nur Typ `listview` |
| Datentabellen der Kataloge | Einträge | alle Tabellen aus `tl_catalog.table` |

`id` und `tstamp` sind nie direkt setzbar.

**Erlaubte Template-Präfixe:** `cm_listing_`, `cm_master_`, `cm_map_view_`, `mod_listing`, `mod_master`, `ce_listview`, `ce_watchlist`.

### Protokollbeispiele

Normalerweise schreibt der Client diese Nachrichten selbst. Zum Verständnis, was zwischen Client und Server läuft (JSON-RPC über stdio):

```json
{"jsonrpc": "2.0", "id": 2, "method": "tools/call",
 "params": {"name": "config_describe", "arguments": {"table": "tl_catalog_field", "context": {"type": "select"}}}}
```

```json
{"jsonrpc": "2.0", "id": 3, "method": "tools/call",
 "params": {"name": "record_list", "arguments": {"table": "cm_events", "where": {"published": "1"}, "orderBy": "startDate DESC", "limit": 10, "columns": ["id", "title", "startDate"]}}}
```

```json
{"jsonrpc": "2.0", "id": 4, "method": "tools/call",
 "params": {"name": "record_delete", "arguments": {"table": "tl_catalog_field", "id": 42}}}
```

Der letzte Aufruf löscht nichts. Er liefert eine Vorschau, was mit dem Feld entfernt würde; erst ein zweiter Aufruf mit `"confirm": true` löscht.

### Anwendungsbeispiele

**1. Bestand abfragen (nur lesen)**

> „Zeig mir alle Kataloge, wie viele Einträge sie haben und welche Module sie ausgeben.“

Der Assistent ruft `catalog_overview` auf und fasst Kataloge, Feldlisten und Module zusammen. Mit `--read-only` ist das risikolos.

> „Welche Veranstaltungen in cm_events sind nicht veröffentlicht?“

`record_list` mit `where: {"published": ""}`.

**2. Neuen Katalog aufbauen**

> „Leg einen Katalog ‚Veranstaltungen‘ mit der Tabelle cm_events an, mit den Feldern Titel (Rolle title), Datum (Rolle startDate), Ort und Kategorie als Auswahl mit den Optionen Konzert, Lesung, Workshop.“

Der Assistent ruft `config_describe` für `tl_catalog` und `tl_catalog_field` auf (z. B. mit `{"type":"select"}`), dann `record_create` für den Katalog, für jedes Feld und für jede Option. Die Callbacks legen Tabelle und Spalten an.

**3. Ausgabe einrichten**

> „Erstelle ein Listen-Modul und ein Detail-Modul für die Veranstaltungen.“

`record_create` in `tl_module` mit den Typen `listing-table` und `master`.

> „Schreib ein Listen-Template cm_listing_events als Karte mit Datum, Titel und Ort und stell es im Listen-Modul ein.“

`template_context` für `cm_events`, dann `template_write` (mit Syntaxprüfung) und `record_update` am Modul (`cmTemplate`). Danach `cache:clear`, damit das Template im Backend auswählbar ist.

**4. Inhalte pflegen und automatisieren**

> „Leg drei Beispiel-Einträge an.“ · „Setze bei allen Workshops im Oktober published auf aus.“

`record_create` bzw. `record_list` und `record_update`. So lassen sich Testdaten für die Entwicklung oder wiederkehrende Pflegeaufgaben per Anfrage erledigen.

**5. Aufräumen mit Sicherheitsnetz**

> „Lösche das Feld ‚Alter Preis‘ aus dem Produktkatalog.“

Der Assistent zeigt erst die Vorschau von `record_delete` und fragt nach. Nach Bestätigung wird gelöscht; die JSON-Sicherung liegt in `var/catalog-mcp/backups/`.

**6. Entwicklung unterstützen**

> „Lies cm_master_default und erkläre, welche Variablen verfügbar sind.“ · „Passe mein cm_master_events so an, dass verwandte Künstler über getRelated('artists') ausgegeben werden.“

`template_read`, `template_context` und `template_write`. Die alte Fassung wird nach `var/catalog-mcp/templates/` gesichert.

### Sicherheit und Grenzen

- **Volle Datenbankrechte:** Der Server läuft mit den Datenbank-Zugangsdaten von Contao, ohne Backend-Benutzer und ohne Rechteprüfung. Wer den SSH-Zugang hat, kann alles, was die Werkzeuge erlauben. Zum Ausprobieren `--read-only` verwenden.
- **Vorher sichern:** Erst auf einer Staging-Kopie oder mit einem aktuellen Datenbank-Backup ausprobieren.
- **Keine Versionierung:** Änderungen erzeugen keine Contao-Versionen (`tl_version`) und kein „Wiederherstellen“. Gelöschte Datensätze liegen als JSON in `var/catalog-mcp/backups/`, ersetzte oder gelöschte Templates in `var/catalog-mcp/templates/`.
- **onload_callbacks:** Sie werden nur für `tl_catalog_palette` ausgeführt, weil dort die Palette vom Datensatz abhängt. Die anderen brauchen einen angemeldeten Backend-Benutzer. Deshalb zeigt `config_describe` bei Katalogen vom Typ „Modifier“ (Erweiterung bestehender Tabellen) evtl. mehr Felder als das Backend.
- **Templates:** Nur `.html5`-Templates mit Catalog-Manager-Präfix. Erscheint ein neues Template im Backend nicht in der Auswahl, `cache:clear --env=prod` ausführen. Templates in Unterordnern müssen im Theme als Template-Ordner eingetragen sein.
- **Frontend-Verwaltung:** Module vom Typ `catalog-frontend-manager` lassen sich über MCP nicht anlegen (nur `listing-table`, `master`, `listing-map`).

---

## 7. Für Entwickler:innen

### Listen per PHP

```php
<?php

use Alnv\ContaoCatalogManagerBundle\Views\Listing;

$arrEntities = (new Listing('cm_events', [
    'column' => ['pid=?'],          // Bedingungen
    'value' => [$this->id],         // Werte je Bedingung
    'order' => 'startDate DESC',    // Reihenfolge
    'limit' => 10,                  // optional
    'masterPage' => 12,             // optional: Seiten-ID der Detailseite (für masterUrl)
]))->parse();
?>

<?php foreach ($arrEntities as $arrEntity): ?>
    <h3><?= $arrEntity['title'] ?></h3>
<?php endforeach; ?>
```

Jeder Eintrag hat dieselben Schlüssel und Funktionen wie in den Templates ([Template-Variablen](#template-variablen-und--funktionen)). Weitere Optionen: `offset`, `pagination`, `groupBy`, `ignoreVisibility`, `ignoreFieldsFromParsing`, `useAbsoluteUrl`.

Für einen einzelnen Eintrag gibt es `Alnv\ContaoCatalogManagerBundle\Views\Master` mit derselben Schnittstelle.

### JSON-Endpunkte (veraltet)

> Diese Endpunkte des `FrontendController` sind **veraltet** (Deprecation-Hinweis im Log) und werden entfernt. Für neue Projekte `Listing` in einem eigenen Controller oder die Frontend-Verwaltung verwenden.

| Route | Methode | Antwort |
|---|---|---|
| `/catalog-manager/json-listing/<MODUL-ID>/<SEITEN-ID>` | GET, POST | `{"results": […], "pagination": …, "limit": …, "max": …}` |
| `/catalog-manager/view-listing/<MODUL-ID>/<SEITEN-ID>` | POST | Gerendertes Modul-HTML (`template`) |
| `/catalog-manager/view-map/<MODUL-ID>/<SEITEN-ID>` | GET | Kartenstandorte |
| `/catalog-manager/icalendar?t=<tabelle>&i=<id>&p=<seite>` | GET | `.ics`-Datei |
| `/catalog-manager/watchlist/update` | POST | Merkliste (CSRF-geschützt) |

Die Seite muss veröffentlicht sein. GET-Parameter (z. B. `?category=123`) werden an die Filter weitergegeben:

```js
async function fetchCatalogData(moduleId, pageId, params = {}) {
    const response = await fetch(`/catalog-manager/json-listing/${moduleId}/${pageId}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(params),
    });

    const data = await response.json();

    return {entries: data.results, pagination: data.pagination, total: data.limit || 0};
}
```

### Hooks

Registrierung wie in Contao üblich, z. B. in `contao/config/config.php`:

```php
$GLOBALS['TL_HOOKS']['parseEntity'][] = [\App\EventListener\CatalogListener::class, 'onParseEntity'];
```

| Hook | Parameter | Zweck |
|---|---|---|
| `parseCatalog` | `$arrCatalog, $wizard` → Rückgabe Katalog | Katalogdefinition beim Aufbau des DCA ändern |
| `parseCatalogField` | `$arrField (DCA), $arrCatalogField, $wizard` → Rückgabe DCA-Feld | DCA eines Feldes ändern |
| `loadVirtualDataContainer` | `$strTable, $vdca` | Nach dem Erzeugen des virtuellen DCA |
| `initializeView` | `$strTable, $arrOptions, $view` | Vor dem Lesen einer Liste bzw. Detailansicht |
| `getModelOptions` | `$arrOptions, $strTable, $arrViewOptions` → Rückgabe Optionen | Abfrage-Optionen ändern |
| `dynModelBuildFindQuery` | `$strQuery, $arrOptions` → Rückgabe SQL | SQL der Abfrage ändern (nicht bei mehrsprachigen Katalogen, deren Abfrage baut dc_multilingual) |
| `parseFieldValue` | `$varValue, $strField, $arrValues, $strTable, $blnFastMode, $view` | Aufbereitung eines Feldwertes |
| `parseEntity` | `&$arrRow, $strTable, $arrOptions, $view` | Eintrag vor der Ausgabe ergänzen |
| `parseViewEntities` | `&$arrEntities, $view` | Alle Einträge einer Ausgabe |
| `roleResolverSetRoles` | `$arrRoles, $arrEntity, $strTable` → Rückgabe Rollen | Rollenzuordnung ändern |
| `replaceActiveInserttag` | `$varValue, $arrFragments` → Rückgabe Wert | Wert von `{{ACTIVE::…}}` ändern |
| `setMetaInformation` | `$objPage` | Metadaten der Detailseite |
| `beforeDownload` | `$strFile` | Vor einem Datei-Download |

Die genauen Signaturen stehen an den Aufrufstellen (`src/Views/View.php`, `src/Helper/CatalogWizard.php`, `src/Library/RoleResolver.php` …).

### Eigene Rollen

**Im Backend:** Catalog Manager → **Rollen** (`tl_catalog_roles`). Dort legst du Bezeichnung, min./max. Länge, min./max. Wert, CSS-Klasse, regulären Ausdruck, Eindeutigkeit und SQL-Datentyp fest.

**Im Code** (`contao/config/config.php`):

```php
$GLOBALS['CM_ROLES']['isbn'] = [
    'group' => 'textfield',
    'eval' => ['maxlength' => 17],
    'sql' => "varchar(17) NOT NULL default ''",
];
```

### Tests

Die Unit-Tests der Frontend-Verwaltung laufen ohne Datenbank und ohne Kernel. PHPUnit ist nicht als Abhängigkeit hinterlegt; verwende z. B. das PHAR von PHPUnit 11:

```bash
# im Contao-Projekt, Bundle unter _packages/ (Entwicklung) oder vendor/
php phpunit.phar -c _packages/contao-catalog-manager-bundle/phpunit.xml.dist
```

`tests/bootstrap.php` sucht den Autoloader des Bundles oder des Projekts. Getestet werden Zugriffsprüfung, Feldregeln, Tabellenfreigaben, Datensatzbereich, Upload-Einstellungen und -Ziele, Datumsspeicherung, die Events und das AJAX-Antwortformat.

Nach Änderungen an Services, Routen oder DCA:

```bash
php vendor/bin/contao-console cache:clear
php vendor/bin/contao-console lint:container
php vendor/bin/contao-console contao:migrate --dry-run
```

---

## 8. Praxisbeispiele

### Beispiel A: Veranstaltungskalender mit Liste und Detailseite

- **Ziel:** Kommende Veranstaltungen als Liste, jede mit eigener Detailseite.
- **Konfiguration:**
  1. Katalog `cm_events` mit den Feldern `title` (Rolle `title`), `alias` (Rolle `alias`), `startDate` (Rolle `startDate`), `teaser`, `image`; Sichtbarkeit aktivieren.
  2. Seite „Veranstaltungen“ mit dem Modul **Liste** (`listing-table`): Tabelle `cm_events`, Detailseite = Unterseite „Veranstaltung“, Sortierung `startDate ASC`, Filter Experte: Spalten `startDate>=?`, Werte `{{TIMESTAMP::dayBegin}}`.
  3. Unterseite „Veranstaltung“ mit dem Modul **Detailansicht** (`master`).
  4. Templates `cm_listing_events` und `cm_master_events` aus `cm_listing_default` bzw. `cm_master_default` ableiten.
- **Ergebnis:** Vergangene Termine werden ausgeblendet. Die Detail-URL entsteht aus dem Alias, und der Seitentitel der Detailseite ist der Titel des Eintrags. Die Einträge erscheinen in der Sitemap.

### Beispiel B: Liste nach Kategorie filtern

- **Ziel:** Über einen Link oder ein Formular nach Kategorie filtern.
- **Konfiguration:** Kategorie-Feld `category` (Auswahl, Mehrfachauswahl). Im Listen-Modul Filter Wizard: Feld `category`, Operator `findInSet`, Wert `{{ACTIVE::category}}`. Formular (Formulargenerator, Methode GET) mit einem Auswahlfeld `category`.
- **Ergebnis:** `/veranstaltungen/?category=3` zeigt nur Einträge der Kategorie 3. Ohne Parameter erscheinen alle.

### Beispiel C: Händlersuche im Umkreis

- **Ziel:** Händler im Umkreis einer Postleitzahl finden.
- **Konfiguration:** Katalog mit `zip`, `city`, `country`, `latitude`, `longitude`, Geocoding aktiviert, Google-API-Schlüssel hinterlegt. Listen- bzw. Karten-Modul mit aktivierter Umkreissuche. Formular (GET) mit `zip`, `country` und `radius`.
- **Ergebnis:** `?zip=06295&country=de&radius=25` listet Händler bis 25 km Entfernung, nach Abstand sortiert.

### Beispiel D: Merkliste mit Zähler im Header

- **Ziel:** Besucher merken sich Produkte; im Header steht die Anzahl.
- **Konfiguration:** In `cm_listing_products` `Watchlist::getForm($this->id, $this->_table, ['title' => $this->title])`. Im Header-Modul `{{WATCHLIST-COUNT}}`, auf der Merklisten-Seite `{{WATCHLIST-TABLE}}`.
- **Ergebnis:** Zähler und Tabelle aktualisieren sich mit der Sitzung.

### Beispiel E: Mitglieder pflegen ihre Inserate

Siehe [Beispiele für die Frontend-Verwaltung](#beispiele-für-die-frontend-verwaltung): Katalog mit Besitzerfeld, Modul „Frontend-Verwaltung“ auf einer geschützten Seite, Uploads ins Home-Verzeichnis.

### Beispiel F: Katalog per KI-Assistent aufsetzen

- **Ziel:** Einen Stellenmarkt ohne Klickarbeit anlegen.
- **Konfiguration:** `php-mcp/server` installieren, Claude Desktop oder Claude Code verbinden ([Clients verbinden](#clients-verbinden)), auf einer Staging-Kopie arbeiten.
- **Anfrage:** *„Leg einen Katalog ‚Stellenangebote‘ (cm_jobs) mit Titel, Alias, Einsatzort, Beginn (Datum), Bewerbungsfrist (Datum) und Beschreibung (Rich Text) an. Erstelle Liste und Detailseite und ein Listen-Template als Karte.“*
- **Ergebnis:** Katalog, Tabelle, Felder, zwei Module und das Template `cm_listing_jobs` sind angelegt. Danach im Backend prüfen, Module in Seiten einbinden und `cache:clear` ausführen.

---

## 9. Häufige Probleme und Lösungen

| Problem | Ursache | Lösung |
|---|---|---|
| Fehler „Unknown column …“ nach Update | Datenbank nicht aktualisiert | `php vendor/bin/contao-console contao:migrate` |
| `There are no commands defined in the "catalog" namespace.` | `php-mcp/server` nicht installiert oder Cache veraltet | `composer require php-mcp/server:^3.3`, danach `cache:clear --env=prod` |
| MCP-Client meldet Verbindungs- oder Parse-Fehler | Ausgabe der Login-Shell auf stdout, Passwortabfrage bei SSH, falsche PHP-Version | SSH-Schlüssel ohne Passphrase-Abfrage, keine Ausgaben in `.bashrc`/`.profile`, vollen PHP-Pfad angeben; den Befehl einmal per SSH von Hand starten |
| Neues Template erscheint nicht in der Auswahl | Template-Cache | `cache:clear --env=prod`; Unterordner im Theme als Template-Ordner eintragen |
| Frontend-Verwaltung: „Sie haben keine Berechtigung für diesen Bereich.“ | Nicht angemeldet, Seite nicht geschützt, Tabelle nicht freigegeben oder kein bearbeitbares Feld | Login prüfen; im Modul bearbeitbare Felder wählen; Hinweise beim Speichern des Moduls lesen |
| Frontend-Verwaltung: Liste leer | Weder Besitzerfeld noch Filter gesetzt, oder bestehende Einträge haben keine Mitglieds-ID | Besitzerfeld wählen und bestehenden Einträgen die Mitglieds-ID setzen bzw. einen Filter definieren |
| „Bearbeiten“ oder „Neu anlegen“ fehlt | Operator nicht aktiviert, oder alle Felder sind Nur-Lese | Operatoren prüfen; Nur-Lese-Haken entfernen (Nur-Lese hat Vorrang) |
| Dateifeld nicht bearbeitbar, Hinweis „Datei-Uploads sind derzeit nicht möglich.“ | Kein gültiges Upload-Ziel (Ordner außerhalb von `files`, Ordner fehlt und `create_folders` ist aus) | Upload-Ordner des Feldes bzw. Parameter `upload_folder` prüfen; Hinweis beim Speichern des Moduls zeigt das Ziel |
| Hochgeladene Bilder sind öffentlich nicht erreichbar | Ordner geschützt (Contao-Standard) | Upload-Ordner in der Dateiverwaltung freigeben |
| Datei wird mit anderem Namen gespeichert | Überschreiben ist bewusst ausgeschlossen | Erwartetes Verhalten |
| TinyMCE erscheint nicht | Feld hat kein `rte`, oder das Skript lädt nicht (Browser-Konsole) | Feldoption „Rich Text Editor“ prüfen; `assets:install`; ohne Editor bleibt das Textarea nutzbar |
| Datum wird abgelehnt | Ungültiges Datum, oder die Uhrzeit hat kein Format `hh:mm` | Picker verwenden; das Format folgt den Contao-Einstellungen |
| AJAX-Fehler „Sitzung abgelaufen“ | Login abgelaufen (401) | Neu anmelden; die Eingaben bleiben auf der Seite |
| Änderungen an CSS/JS wirken nicht | Combiner- bzw. Browser-Cache | `assets:install public`, Skript-Cache in der Systemwartung leeren, Browser-Cache leeren |
| Fehler „Invalid table name …“ | Ungültiger Tabellenname (nur `A-Z`, `a-z`, `0-9`, `_`, max. 64 Zeichen) | Tabellennamen des Katalogs korrigieren |
| Umkreissuche liefert nichts | Kein API-Schlüssel, Koordinaten fehlen, Parameter falsch benannt | Schlüssel in den Einstellungen, Geocoding im Katalog, Feldnamen `zip`/`city`/`radius` |

Technische Details zu Fehlern stehen in `var/logs/prod-<datum>.log`.

---

## 10. Änderungen gegenüber v1

- `{{CTLG_ACTIVE}}` wird zu `{{ACTIVE}}`.
- `{{CTLG_MASTER}}` wird zu `{{MASTER}}`; Einstellungen in der Seitenstruktur sind nicht mehr nötig.
- `{{CTLG_TIMESTAMP}}` wird zu `{{TIMESTAMP}}`, z. B. `{{TIMESTAMP::tstamp::+ 1 days}}`.
- Es gibt kein eigenes Filterformular mehr; stattdessen den Formulargenerator verwenden.
- Frontend-Editing: seit 4.1 als [Frontend-Verwaltung](#5-frontend-verwaltung-frontend-manager) verfügbar.
- Modifizierer sind nicht verfügbar.
- Neue Feldnamen der Umkreissuche:
  - `rs_pstl` → `postal` (oder `zip`)
  - `rs_cty` → `city`
  - `rs_strt` → `street` (Hausnummer separat in `streetNumber`)
  - `rs_cntry` → `country`
  - `rs_dstnc` → `radius`
- Backend-CSS-Klassen, SQL-Typen und reguläre Ausdrücke werden über [Rollen](#felder-und-rollen) festgelegt.

### Update von v2

- Das Frontend-Modul „Listenansicht“ hieß in v2 `listing` (kollidierte mit dem Listing-Modul von Contao) und heißt seit v3 `listing-table`; das Hüllen-Template `mod_listing` heißt jetzt `mod_listing_table`. Die Migration `ListingModuleTypeMigration` stellt bestehende Module automatisch um:

  ```bash
  php vendor/bin/contao-console contao:migrate --migrations-only --dry-run   # anstehende Migrationen anzeigen
  php vendor/bin/contao-console contao:migrate --migrations-only
  ```

  Umgestellt werden nur Module mit Katalog-Tabelle (`cmTable`); Module des Contao-Listing-Moduls bleiben unberührt. Überschreibt das Projekt `mod_listing.html5`, wird es weiterverwendet (Hinweis in der Ausgabe: am besten in `mod_listing_table_*.html5` umbenennen).
- Insert-Tags in Großbuchstaben (`{{ACTIVE::…}}` usw.) in den Filtereinstellungen schreibt `InsertTagCaseMigration` klein.

---

## 11. Support

- Website und Dokumentation: <https://catalog-manager.org>
- E-Mail: <support@catalog-manager.org>
- Issues: <https://github.com/alnv/contao-catalog-manager-bundle/issues>
- Entwickler: Alexander Naumov, <https://alexandernaumov.de>
