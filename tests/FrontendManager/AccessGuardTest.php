<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessDeniedException;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\ManagerConfig;
use PHPUnit\Framework\TestCase;

final class AccessGuardTest extends TestCase
{
    public function testGuestIsNotAuthenticated(): void
    {
        $guard = Fixtures::guard(guest: true);

        $this->assertFalse($guard->isAuthenticated());
        $this->assertDenied(AccessDeniedException::NOT_AUTHENTICATED, static fn () => $guard->assertCanUse());
        $this->assertFalse($guard->canPerform(ManagerConfig::OPERATION_EDIT));
    }

    public function testAnyLoggedInMemberHasModuleAccessWithoutGroups(): void
    {
        $guard = Fixtures::guard([], Fixtures::member(7));

        $guard->assertCanUse();
        $this->assertTrue($guard->canPerform(ManagerConfig::OPERATION_EDIT));
    }

    public function testTableNotAllowed(): void
    {
        $guard = Fixtures::guard([], null, false);

        $this->assertDenied(AccessDeniedException::TABLE, static fn () => $guard->assertCanUse());
    }

    public function testOperationMustBeConfigured(): void
    {
        $guard = Fixtures::guard(['operations' => [ManagerConfig::OPERATION_EDIT]]);

        $this->assertTrue($guard->canPerform(ManagerConfig::OPERATION_EDIT));
        $this->assertDenied(AccessDeniedException::OPERATION, static fn () => $guard->assertOperation(ManagerConfig::OPERATION_DELETE));
        $this->assertDenied(AccessDeniedException::OPERATION, static fn () => $guard->assertOperation('drop_table'));
    }

    public function testToggleNeedsAValidVisibilityField(): void
    {
        $this->assertFalse(Fixtures::guard(['toggleField' => null])->canPerform(ManagerConfig::OPERATION_TOGGLE));
        $this->assertFalse(Fixtures::guard(['toggleField' => 'tags'])->canPerform(ManagerConfig::OPERATION_TOGGLE));
        $this->assertTrue(Fixtures::guard()->canPerform(ManagerConfig::OPERATION_TOGGLE));
    }

    public function testEditNeedsEditableFields(): void
    {
        $guard = Fixtures::guard(['formFields' => ['id', 'password']]);

        $this->assertFalse($guard->canPerform(ManagerConfig::OPERATION_EDIT));
        $this->assertFalse($guard->canPerform(ManagerConfig::OPERATION_CREATE));
        $this->assertTrue($guard->canPerform(ManagerConfig::OPERATION_DELETE));
    }

    public function testEditableFields(): void
    {
        // alias: eval.readonly, category: als Nur-Lese konfiguriert, owner: Besitzerfeld,
        // password: geheim, id: System, notes: doNotShow, color: kein Frontend-Widget.
        // Dateifelder sind bearbeitbar (Upload-Ziel aus DCA bzw. global, kein Modul-Ordner nötig).
        $this->assertSame(['title', 'image'], Fixtures::guard()->getEditableFields());
    }

    public function testInternalValuesAreNeverEditable(): void
    {
        $guard = Fixtures::guard(['readonlyFields' => [], 'internalValues' => ['category' => 'a']]);

        $this->assertSame(['title', 'image'], $guard->getEditableFields());
    }

    public function testReadonlyFields(): void
    {
        $this->assertSame(['category', 'owner', 'alias'], Fixtures::guard()->getReadonlyFields());
    }

    public function testAssertFieldsEditableRejectsManipulatedFields(): void
    {
        $guard = Fixtures::guard();
        $guard->assertFieldsEditable(['title' => 'x']);

        foreach (['owner', 'category', 'alias', 'id', 'password', 'unknown'] as $field) {
            $this->assertDenied(AccessDeniedException::FIELD, static fn () => $guard->assertFieldsEditable(['title' => 'x', $field => '1']));
        }
    }

    public function testListAndFilterFields(): void
    {
        $guard = Fixtures::guard();

        $this->assertSame(['title', 'category', 'id'], $guard->getListFields());
        $this->assertSame(['title', 'category', 'tags', 'published'], $guard->getFilterFields());
        $this->assertSame([], $guard->getSortableFields(), 'Sortierung nicht freigegeben');
        $this->assertSame(['title', 'category', 'id'], Fixtures::guard(['sortable' => true])->getSortableFields());
    }

    public function testInternalValuesDropInvalidFields(): void
    {
        $guard = Fixtures::guard(['internalValues' => ['pid' => '3', 'tstamp' => '1', 'owner' => '99', 'category' => 'a', 'nope' => 'x', 'password' => 'p']]);

        $this->assertSame(['pid' => '3', 'category' => 'a'], $guard->getInternalValues());
    }

    public function testOwnerFieldMustBeEligible(): void
    {
        $this->assertSame('owner', Fixtures::guard()->getOwnerField());
        $this->assertNull(Fixtures::guard(['ownerField' => 'id'])->getOwnerField());
        $this->assertNull(Fixtures::guard(['ownerField' => 'virtual'])->getOwnerField());
    }

    private function assertDenied(string $level, callable $callback): void
    {
        try {
            $callback();
            $this->fail('AccessDeniedException expected');
        } catch (AccessDeniedException $e) {
            $this->assertSame($level, $e->getLevel());
        }
    }
}
