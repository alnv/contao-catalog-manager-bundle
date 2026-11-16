<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

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
