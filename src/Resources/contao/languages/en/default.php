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
    'wizard' => 'Wizard'
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
$GLOBALS['TL_LANG']['MSC']['watchListUnitLabel'] = 'Quantity';
$GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'] = 'Add to watchlist';
$GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'] = 'Remove from watchlist';
$GLOBALS['TL_LANG']['MSC']['watchListUnitsButtonLabel'] = 'Change quantity';

// Custom-Option-Wizard (Backend)
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['add'] = 'Add';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['newOption'] = 'Add new entry';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['placeholder'] = 'Enter a new entry …';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['empty'] = 'No entries yet.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['remove'] = 'Delete “%s”';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['confirmRemove'] = 'Do you really want to permanently delete “%s”?';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['added'] = '“%s” has been added and selected.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['removed'] = '“%s” has been deleted.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['exists'] = '“%s” already exists and has been selected.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['emptyInput'] = 'Please enter a name.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['saving'] = 'Saving …';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['forbidden'] = 'You are not allowed to perform this action.';
$GLOBALS['TL_LANG']['MSC']['cmOptionWizard']['error'] = 'The action failed. Please reload the page and try again.';

// Merkliste (Frontend-Widget)
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['add'] = 'Add to watchlist';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['remove'] = 'Remove from watchlist';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['units'] = 'Quantity';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['decrease'] = 'Decrease quantity';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['increase'] = 'Increase quantity';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['added'] = 'Added to watchlist.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['removed'] = 'Removed from watchlist.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['unitsUpdated'] = 'Quantity changed to %s.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['saving'] = 'Saving …';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['tooMany'] = 'Too many requests. Please wait a moment and try again.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['invalidToken'] = 'Your session has expired. Please reload the page.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['full'] = 'The watchlist is full.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['error'] = 'The watchlist could not be saved. Please try again.';
$GLOBALS['TL_LANG']['MSC']['cmWatchlist']['noscript'] = 'JavaScript is required for the watchlist.';

// Frontend-Verwaltung
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['loginRequired'] = 'Please log in to manage your entries.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['accessDenied'] = 'You are not allowed to access this area.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['recordDenied'] = 'The entry does not exist or you are not allowed to edit it.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saveDenied'] = 'The entry could not be saved with these values.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['error'] = 'An unexpected error occurred. Please try again.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saved'] = 'The entry has been saved.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copied'] = 'The entry has been duplicated.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleted'] = 'The entry has been deleted.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['published'] = 'The entry is now visible.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['unpublished'] = 'The entry is now hidden.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['empty'] = 'No entries found.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['create'] = 'New entry';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['createHeadline'] = 'New entry';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editHeadline'] = 'Edit entry %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editHeadlineTitle'] = 'Edit "%s"';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['edit'] = 'Edit';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['editLabel'] = 'Edit %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copy'] = 'Duplicate';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['copyLabel'] = 'Duplicate %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['delete'] = 'Delete';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteLabel'] = 'Delete %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteHeadline'] = 'Delete entry';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteConfirm'] = 'Do you really want to delete "%s"? This cannot be undone.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['deleteSubmit'] = 'Delete permanently';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['cancel'] = 'Cancel';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['save'] = 'Save';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['saveClose'] = 'Save and close';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['reload'] = 'Update form';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['formErrors'] = 'Please correct the marked fields.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filter'] = 'Filter';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterAll'] = 'All';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterApply'] = 'Apply filter';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filterReset'] = 'Reset';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['yes'] = 'Yes';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['no'] = 'No';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['caption'] = '%d entries';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['actions'] = 'Actions';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['visibility'] = 'Visibility';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['visible'] = 'visible';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['hidden'] = 'hidden';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['toggleHint'] = 'toggle visibility of "%s"';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['loading'] = 'Loading …';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['busy'] = 'Please wait, an action is still in progress.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['networkError'] = 'No connection to the server. Please check your internet connection and try again.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['timeout'] = 'The server did not respond in time. Please try again.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['invalidResponse'] = 'The server sent an unexpected response. Please try again.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['sessionExpired'] = 'Your session has expired. Please log in again. Your input remains on this page until then.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['csrf'] = 'Your session has expired. Please try again.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['databaseError'] = 'The data could not be saved. Please try again later.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['badRequest'] = 'Invalid request.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['dismiss'] = 'Close message';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['currentFiles'] = 'Current files: %s';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['filePending'] = 'uploaded, will be saved with the form';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['fileRemove'] = 'remove';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadHint'] = 'Allowed file types: %s. Maximum file size: %s.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadUnavailable'] = 'File uploads are currently not possible.';
$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadInvalidImage'] = 'The file "%s" is not a valid image.';
