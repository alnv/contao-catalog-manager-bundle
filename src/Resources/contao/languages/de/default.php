<?php

$GLOBALS['TL_LANG']['MSC']['optionSourceDbTable'] = ['Tabelle', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbKey'] = ['Datenbankwert', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbLabel'] = ['Bezeichnung', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterType'] = ['Eingabetyp', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterColumn'] = ['Spalten', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterValue'] = ['Werte', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbWizardFilterSettings'] = ['Filtereinstellungen', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbOrderField'] = ['Sortierungsfeld', ''];
$GLOBALS['TL_LANG']['MSC']['optionSourceDbOrder'] = ['Reihenfolge', ''];
$GLOBALS['TL_LANG']['MSC']['reference']['optionSourceDbFilterType'] = [
    'expert' => 'Experte',
    'wizard' => 'Wizard (Klassisch)'
];
$GLOBALS['TL_LANG']['MSC']['reference']['optionSourceDbOrder'] = [
    'asc' => 'Aufsteigend',
    'desc' => 'Absteigend'
];

$GLOBALS['TL_LANG']['DCA']['preview'] = ['Vorschau', 'In der Vorschau öffnen.'];
$GLOBALS['TL_LANG']['MSC']['tstamp'] = ['Änderungsdatum', 'Datum und Uhrzeit der letzten Änderung.'];
$GLOBALS['TL_LANG']['MSC']['start'] = ['Anzeigen ab', 'Den Artikel erst ab diesem Datum anzeigen.'];
$GLOBALS['TL_LANG']['MSC']['stop'] = ['Anzeigen bis', 'Den Artikel nur bis zu diesem Datum anzeigen.'];
$GLOBALS['TL_LANG']['MSC']['published'] = ['Artikel veröffentlichen', 'Den Artikel auf der Webseite anzeigen.'];
$GLOBALS['TL_LANG']['MSC']['alias'] = ['Artikelalias', 'Der Artikelalias ist eine eindeutige Referenz, die anstelle der numerischen Artikel-ID aufgerufen werden kann.'];

$GLOBALS['TL_LANG']['MSC']['watchListTitleLabel'] = 'Name';
$GLOBALS['TL_LANG']['MSC']['watchListUnitLabel'] = 'Menge';
$GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'] = 'Zur Wunschliste hinzufügen';
$GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'] = 'Aus der Wunschliste entfernen';
$GLOBALS['TL_LANG']['MSC']['watchListUnitsButtonLabel'] = 'Anzahl ändern';

// Custom-Option-Wizard (Backend)
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['add'] = 'Hinzufügen';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['newOption'] = 'Neuen Eintrag hinzufügen';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['placeholder'] = 'Neuen Eintrag eingeben …';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['empty'] = 'Noch keine Einträge vorhanden.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['remove'] = '„%s“ löschen';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['confirmRemove'] = 'Soll „%s“ endgültig gelöscht werden?';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['added'] = '„%s“ wurde hinzugefügt und ausgewählt.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['removed'] = '„%s“ wurde gelöscht.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['exists'] = '„%s“ ist bereits vorhanden und wurde ausgewählt.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['emptyInput'] = 'Bitte einen Namen eingeben.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['saving'] = 'Wird gespeichert …';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['forbidden'] = 'Keine Berechtigung für diese Aktion.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['error'] = 'Die Aktion ist fehlgeschlagen. Bitte die Seite neu laden und erneut versuchen.';

// Merkliste (Frontend-Widget)
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['add'] = 'Zur Wunschliste hinzufügen';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['remove'] = 'Von der Wunschliste entfernen';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['units'] = 'Menge';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['decrease'] = 'Menge verringern';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['increase'] = 'Menge erhöhen';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['added'] = 'Zur Wunschliste hinzugefügt.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['removed'] = 'Von der Wunschliste entfernt.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['unitsUpdated'] = 'Menge auf %s geändert.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['saving'] = 'Wird gespeichert …';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['tooMany'] = 'Zu viele Anfragen. Bitte kurz warten und erneut versuchen.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['invalidToken'] = 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['full'] = 'Die Wunschliste ist voll.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['error'] = 'Die Wunschliste konnte nicht gespeichert werden. Bitte erneut versuchen.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['noscript'] = 'Für die Wunschliste wird JavaScript benötigt.';

// Frontend-Verwaltung
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['loginRequired'] = 'Bitte melden Sie sich an, um Ihre Einträge zu verwalten.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['accessDenied'] = 'Sie haben keine Berechtigung für diesen Bereich.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['recordDenied'] = 'Der Eintrag existiert nicht oder Sie dürfen ihn nicht bearbeiten.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saveDenied'] = 'Der Eintrag konnte mit diesen Werten nicht gespeichert werden.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['error'] = 'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saved'] = 'Der Eintrag wurde gespeichert.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copied'] = 'Der Eintrag wurde dupliziert.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleted'] = 'Der Eintrag wurde gelöscht.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['published'] = 'Der Eintrag ist jetzt sichtbar.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['unpublished'] = 'Der Eintrag ist jetzt ausgeblendet.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['empty'] = 'Keine Einträge gefunden.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['create'] = 'Neuer Eintrag';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['createHeadline'] = 'Neuer Eintrag';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editHeadline'] = 'Eintrag %s bearbeiten';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editHeadlineTitle'] = '„%s“ bearbeiten';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['edit'] = 'Bearbeiten';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editLabel'] = '%s bearbeiten';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copy'] = 'Duplizieren';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copyLabel'] = '%s duplizieren';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['delete'] = 'Löschen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteLabel'] = '%s löschen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteHeadline'] = 'Eintrag löschen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteConfirm'] = 'Möchten Sie „%s“ wirklich löschen? Dies kann nicht rückgängig gemacht werden.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteSubmit'] = 'Endgültig löschen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['cancel'] = 'Abbrechen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['save'] = 'Speichern';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saveClose'] = 'Speichern und schließen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['reload'] = 'Formular aktualisieren';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['formErrors'] = 'Bitte korrigieren Sie die markierten Felder.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filter'] = 'Filter';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterAll'] = 'Alle';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterApply'] = 'Filter anwenden';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterReset'] = 'Zurücksetzen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['yes'] = 'Ja';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['no'] = 'Nein';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['caption'] = '%d Einträge';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['actions'] = 'Aktionen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['visibility'] = 'Sichtbarkeit';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['visible'] = 'sichtbar';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['hidden'] = 'ausgeblendet';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['toggleHint'] = 'Sichtbarkeit von „%s“ umschalten';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['loading'] = 'Wird geladen …';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['busy'] = 'Bitte warten, eine Aktion wird noch ausgeführt.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['networkError'] = 'Keine Verbindung zum Server. Bitte prüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['timeout'] = 'Der Server hat nicht rechtzeitig geantwortet. Bitte versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['invalidResponse'] = 'Der Server hat eine unerwartete Antwort geschickt. Bitte versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['sessionExpired'] = 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an. Ihre Eingaben bleiben bis dahin auf dieser Seite erhalten.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['csrf'] = 'Die Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['databaseError'] = 'Die Daten konnten nicht gespeichert werden. Bitte versuchen Sie es später erneut.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['badRequest'] = 'Ungültige Anfrage.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['dismiss'] = 'Meldung schließen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['currentFiles'] = 'Vorhandene Dateien: %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filePending'] = 'hochgeladen, wird mit dem Formular gespeichert';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['fileRemove'] = 'entfernen';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadHint'] = 'Erlaubte Dateitypen: %s. Maximale Dateigröße: %s.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadUnavailable'] = 'Datei-Uploads sind derzeit nicht möglich.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadInvalidImage'] = 'Die Datei „%s“ ist kein gültiges Bild.';
