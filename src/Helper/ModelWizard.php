<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Contao\Model;

class ModelWizard
{
    public const GENERATED_NAMESPACE = 'Alnv\\ContaoCatalogManagerBundle\\Models\\Generated';

    protected ?Model $objModel = null;

    public function __construct(string $table)
    {

        $GLOBALS['CM_TEMP_MODEL_TABLE'] = $table;

        if ($model = $GLOBALS['TL_MODELS'][$table] ?? '') {
            $this->objModel = new $model();
            return;
        }

        $strBaseModel = DynModel::class;
        $strGlobalModel = $GLOBALS['CM_MODELS'][$table] ?? '';

        if ($strGlobalModel && $this->modelExist($strGlobalModel)) {
            $strBaseModel = $strGlobalModel;
        }

        $strClass = self::getModelClassForTable($strBaseModel, $table);

        $this->objModel = new $strClass();
        $GLOBALS['TL_MODELS'][$table] = $strClass;
    }

    public static function getModelClassForTable(string $strBaseModel, string $strTable): string
    {
        if (!CatalogTableGuard::isValidIdentifier($strTable)) {
            throw new \InvalidArgumentException(sprintf('Invalid table name "%s".', $strTable));
        }

        if (!\class_exists($strBaseModel) || !\is_subclass_of($strBaseModel, Model::class)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a Contao model.', $strBaseModel));
        }

        $objReflection = new \ReflectionClass($strBaseModel);
        if ($objReflection->isFinal()) {
            throw new \InvalidArgumentException(sprintf('Model "%s" must not be final.', $strBaseModel));
        }

        $strShortName = 'T_' . \substr(\md5($strBaseModel), 0, 8) . '_' . $strTable;
        $strClass = self::GENERATED_NAMESPACE . '\\' . $strShortName;

        if (!\class_exists($strClass, false)) {
            // Nur validierte Identifier ([A-Za-z0-9_]) und ein existierender Klassenname werden eingesetzt
            eval(\sprintf(
                'namespace %s; class %s extends \\%s { public static $strTable = %s; }',
                self::GENERATED_NAMESPACE,
                $strShortName,
                \ltrim($strBaseModel, '\\'),
                \var_export($strTable, true)
            ));
        }

        return $strClass;
    }

    public function getModel(): ?Model
    {
        return $this->objModel;
    }

    protected function modelExist(string $strModel): bool
    {
        return \class_exists($strModel);
    }
}