<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Multilingual;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Alnv\ContaoCatalogManagerBundle\Models\MultilingualDynModel;
use PHPUnit\Framework\TestCase;
use Terminal42\DcMultilingualBundle\Model\MultilingualTrait;

/**
 * Struktur und Abfrage-Optionen von MultilingualDynModel ohne Datenbank: find() wird in den
 * Test-Models abgefangen und liefert die zusammengesetzten Optionen zurück.
 */
final class MultilingualDynModelTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']['cm_ml_test']);
    }

    public function testSharesDynModelArchitecture(): void
    {
        $this->assertTrue(is_subclass_of(MultilingualDynModel::class, DynModel::class), 'countWithOptions() und instanceof DynModel (View::countEntities) greifen');
        $this->assertContains(MultilingualTrait::class, class_uses(MultilingualDynModel::class));
    }

    public function testBaseModelsKeepSeparateTableState(): void
    {
        $property = new \ReflectionProperty(MultilingualDynModel::class, 'strTable');

        $this->assertSame(MultilingualDynModel::class, $property->getDeclaringClass()->getName(), 'eigene statische $strTable, nicht die von DynModel');
    }

    public function testModelWizardGeneratesTableBoundSubclass(): void
    {
        $class = ModelWizard::getModelClassForTable(MultilingualDynModel::class, 'cm_ml_test');

        $this->assertTrue(is_subclass_of($class, MultilingualDynModel::class));
        $this->assertSame('cm_ml_test', $class::getTable());
        $this->assertSame('', MultilingualDynModel::getTable());
    }

    public function testFindByIdOrAliasWithMultilingualAlias(): void
    {
        $GLOBALS['TL_DCA']['cm_ml_test']['fields']['alias']['eval']['isMultilingualAlias'] = true;

        $this->assertSame([
            'column' => ['cm_ml_test.published=?', '(cm_ml_test.alias=? OR translation.alias=?)'],
            'value' => ['1', 'my-alias', 'my-alias'],
            'limit' => 1,
            'return' => 'Model',
        ], CapturingMultilingualModel::findByIdOrAlias('my-alias', ['column' => ['cm_ml_test.published=?'], 'value' => ['1']]));

        $this->assertSame(['(cm_ml_test.id=? OR translation.id=?)'], CapturingMultilingualModel::findByIdOrAlias('42')['column']);
    }

    public function testFindByIdOrAliasWithoutMultilingualAlias(): void
    {
        $this->assertSame(
            ['column' => ['cm_ml_test.alias=?'], 'value' => ['my-alias'], 'limit' => 1, 'return' => 'Model'],
            CapturingMultilingualModel::findByIdOrAlias('my-alias')
        );
    }

    public function testDynModelFindByIdOrAliasUnchanged(): void
    {
        $this->assertSame(
            ['column' => ['cm_dyn_test.id=?'], 'value' => [7], 'limit' => 1, 'return' => 'Model'],
            CapturingDynModel::findByIdOrAlias(7)
        );
        $this->assertSame(['cm_dyn_test.alias=?'], CapturingDynModel::findByIdOrAlias('07')['column'], 'führende Null = Alias');
    }
}

class CapturingMultilingualModel extends MultilingualDynModel
{
    public static $strTable = 'cm_ml_test';

    protected static function find(array $arrOptions)
    {
        return $arrOptions;
    }
}

class CapturingDynModel extends DynModel
{
    public static $strTable = 'cm_dyn_test';

    protected static function find(array $arrOptions)
    {
        return $arrOptions;
    }
}
