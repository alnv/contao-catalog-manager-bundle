<?php

$GLOBALS['TL_LANG']['tl_module']['master_settings'] = 'Detail-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['listing_settings'] = 'Listen-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['radius_search_settings'] = 'Umkreissuche-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['performance_settings'] = 'Performance-Einstellungen';

$GLOBALS['TL_LANG']['tl_module']['cmTable'] = ['Tabelle', ''];
$GLOBALS['TL_LANG']['tl_module']['cmTemplate'] = ['Template', ''];
$GLOBALS['TL_LANG']['tl_module']['cmPagination'] = ['Pagination anzeigen', ''];
$GLOBALS['TL_LANG']['tl_module']['cmLimit'] = ['Limit', ''];
$GLOBALS['TL_LANG']['tl_module']['cmOffset'] = ['Überspringen', ''];
$GLOBALS['TL_LANG']['tl_module']['cmGroupBy'] = ['Gruppieren', ''];
$GLOBALS['TL_LANG']['tl_module']['cmGroupByHl'] = ['Gruppen-Überschrift', ''];
$GLOBALS['TL_LANG']['tl_module']['cmFilter'] = ['Filter anwenden', ''];
$GLOBALS['TL_LANG']['tl_module']['cmColumn'] = ['Spalten', ''];
$GLOBALS['TL_LANG']['tl_module']['cmValue'] = ['Werte', ''];
$GLOBALS['TL_LANG']['tl_module']['cmMaster'] = ['Detailseite verwenden', ''];
$GLOBALS['TL_LANG']['tl_module']['cmMasterPage'] = ['Detailseite auswählen', ''];
$GLOBALS['TL_LANG']['tl_module']['cmMasterModule'] = ['Detailansicht auswählen', ''];
$GLOBALS['TL_LANG']['tl_module']['cmRadiusSearch'] = ['Umkreissuche aktivieren', ''];
$GLOBALS['TL_LANG']['tl_module']['cmFilterType'] = ['Eingabetyp', ''];
$GLOBALS['TL_LANG']['tl_module']['cmWizardFilterSettings'] = ['Filtereinstellungen', ''];
$GLOBALS['TL_LANG']['tl_module']['cmInfoContent'] = ['Info-Fenster Inhalt', ''];
$GLOBALS['TL_LANG']['tl_module']['cmOrder'] = ['Sortierung', ''];
$GLOBALS['TL_LANG']['tl_module']['cmIgnoreVisibility'] = ['Elemente immer anzeigen', 'Auch unveröffentlichte Daten werden angezeigt'];
$GLOBALS['TL_LANG']['tl_module']['cmIgnoreFieldsFromParsing'] = ['Felder nicht parsen', ''];

$GLOBALS['TL_LANG']['tl_module']['reference']['cmFilterType'] = [
    'expert' => 'Experte',
    'wizard' => 'Wizard (Klassisch)'
];

// Frontend-Verwaltung
$GLOBALS['TL_LANG']['tl_module']['cm_fm_table_legend'] = 'Tabelle';
$GLOBALS['TL_LANG']['tl_module']['cm_fm_fields_legend'] = 'Felder';
$GLOBALS['TL_LANG']['tl_module']['cm_fm_list_legend'] = 'Listenansicht';
$GLOBALS['TL_LANG']['tl_module']['cm_fm_operations_legend'] = 'Operatoren';
$GLOBALS['TL_LANG']['tl_module']['cm_fm_permission_legend'] = 'Datensatzbeschränkung';
$GLOBALS['TL_LANG']['tl_module']['cm_fm_upload_legend'] = 'Datei-Uploads';
$GLOBALS['TL_LANG']['tl_module']['cmFmTableGroups'] = ['catalog' => 'Catalog-Manager-Tabellen', 'contao' => 'Freigegebene Contao-Tabellen'];
$GLOBALS['TL_LANG']['tl_module']['cmFmListFields'] = ['Spalten der Listenansicht', 'Felder und Reihenfolge der Spalten. Die erste Spalte dient als Titel des Datensatzes.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmFormFields'] = ['Bearbeitbare Felder', 'Nur diese Felder werden aus dem Formular übernommen. Reihenfolge und Gruppen folgen der DCA-Palette.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmReadonlyFields'] = ['Nur-Lese-Felder', 'Werden im Formular angezeigt, aber nie gespeichert.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmFilterFields'] = ['Filterfelder', 'Felder, nach denen Frontend-Benutzer die Liste filtern dürfen.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmSortable'] = ['Sortierung erlauben', 'Spalten der Listenansicht sind per Klick sortierbar.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmOperations'] = ['Erlaubte Operatoren', 'Werden bei jeder Anfrage serverseitig geprüft.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmOperationsReference'] = ['create' => 'Neu anlegen', 'edit' => 'Bearbeiten', 'copy' => 'Duplizieren', 'delete' => 'Löschen', 'toggle' => 'Sichtbarkeit umschalten'];
$GLOBALS['TL_LANG']['tl_module']['cmFmToggleField'] = ['Sichtbarkeitsfeld', 'Checkbox-Feld, das die Sichtbarkeit steuert (z. B. published oder invisible).'];
$GLOBALS['TL_LANG']['tl_module']['cmFmToggleReverse'] = ['Umgekehrte Logik', 'Aktiviert = unsichtbar (z. B. Feld "invisible").'];
$GLOBALS['TL_LANG']['tl_module']['cmFmOwnerField'] = ['Besitzerfeld', 'Feld mit der ID des Mitglieds. Neue Datensätze erhalten automatisch die ID des angemeldeten Mitglieds; es ist nie bearbeitbar.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmInternalValues'] = ['Interne Werte', 'Werte, die beim Anlegen gesetzt werden (Insert-Tags erlaubt, z. B. pid = 3). Diese Felder sind nicht bearbeitbar.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmNoRestriction'] = 'Weder Besitzerfeld noch Filter gesetzt: Aus Sicherheitsgründen sind im Frontend keine Datensätze zugänglich.';
$GLOBALS['TL_LANG']['tl_module']['cmFmOwnerReadonly'] = 'Das Besitzerfeld ist nie bearbeitbar und wird ignoriert.';
$GLOBALS['TL_LANG']['tl_module']['cmFmToggleMissing'] = 'Für "Sichtbarkeit umschalten" muss ein Sichtbarkeitsfeld gewählt werden.';
$GLOBALS['TL_LANG']['tl_module']['cmFmMandatoryMissing'] = 'Pflichtfelder, die weder bearbeitbar noch intern gesetzt sind: %s';
$GLOBALS['TL_LANG']['tl_module']['cmFmParentMissing'] = 'Neue Datensätze benötigen einen Elterndatensatz in %s. Bitte "pid" als internen Wert setzen.';
$GLOBALS['TL_LANG']['tl_module']['cmFmReadonlyOverlap'] = 'Diese Felder sind zugleich als Nur-Lese markiert und deshalb nicht bearbeitbar: %s';
$GLOBALS['TL_LANG']['tl_module']['cmFmNoEditableFields'] = 'Kein Feld ist bearbeitbar: "Neu anlegen" und "Bearbeiten" stehen im Frontend nicht zur Verfügung. Bitte Nur-Lese-Felder prüfen.';
$GLOBALS['TL_LANG']['tl_module']['cmFmInvalidInternalField'] = 'Das Feld "%s" kann nicht intern gesetzt werden.';
$GLOBALS['TL_LANG']['tl_module']['cmFmUseHomeDir'] = ['Ein Home-Verzeichnis festlegen', 'Uploads werden im Home-Verzeichnis des angemeldeten Mitglieds gespeichert (wird bei Bedarf angelegt und dem Mitglied zugewiesen). Sonst gilt der Upload-Ordner des Feldes bzw. der globale Standardordner.'];
$GLOBALS['TL_LANG']['tl_module']['cmFmUploadTargets'] = 'Upload-Ziele: %s';
$GLOBALS['TL_LANG']['tl_module']['cmFmUploadHome'] = 'Home-Verzeichnis des Mitglieds';
$GLOBALS['TL_LANG']['tl_module']['cmFmUploadInvalid'] = 'Upload-Ordner für "%s" ist ungültig oder liegt außerhalb von %s: Uploads sind für dieses Feld nicht möglich.';
