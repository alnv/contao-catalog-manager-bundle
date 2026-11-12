<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

/**
 * Event-Namen der Frontend-Verwaltung (Modultyp catalog-frontend-manager).
 *
 * Alle Events werden NACH erfolgreichem Speichern ausgelöst – der Datensatz ist geschrieben, alle
 * Berechtigungsprüfungen und DCA-Callbacks sind gelaufen. Typische Verwendung: Benachrichtigungen,
 * Schnittstellen zu externen Systemen, Protokollierung, Cache-Invalidierung.
 *
 * Listener registrieren (Symfony):
 *
 *     #[AsEventListener(FrontendManagerEvents::RECORD_UPDATED)]
 *     public function __invoke(RecordUpdatedEvent $event): void { … }
 *
 * Fehler in Listenern brechen die Aktion des Mitglieds nicht ab: Sie werden im Contao-Log
 * protokolliert, der gespeicherte Datensatz bleibt erhalten.
 */
final class FrontendManagerEvents
{
    /** Datensatz angelegt (auch beim Duplizieren, siehe RecordCreatedEvent::isCopy()). */
    public const RECORD_CREATED = 'catalog_manager.frontend_manager.record_created';

    /** Datensatz bearbeitet; enthält alte und neue Daten. */
    public const RECORD_UPDATED = 'catalog_manager.frontend_manager.record_updated';

    /** Datensatz gelöscht; enthält die Daten vor dem Löschen. */
    public const RECORD_DELETED = 'catalog_manager.frontend_manager.record_deleted';

    /** Sichtbarkeit umgeschaltet (Operator "Sichtbarkeit"). */
    public const RECORD_VISIBILITY_CHANGED = 'catalog_manager.frontend_manager.record_visibility_changed';

    private function __construct()
    {
    }
}
